<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Transaction;
use Carbon\Carbon;

class ForecastingService
{
    /**
     * Calculate Simple Moving Average (SMA) demand for an item.
     *
     * @param Item $item
     * @param int $windowMonths Window period in months (default: 3 months)
     * @param int $historyMonths Total historical months to display (default: 12, can be 36 or 60)
     * @return array
     */
    public function calculateMovingAverage(Item $item, int $windowMonths = 3, int $historyMonths = 12): array
    {
        $now = Carbon::now();

        // Ambil histori bulanan pengeluaran barang (Outbound) selama $historyMonths terakhir
        $history = [];
        for ($i = ($historyMonths - 1); $i >= 0; $i--) {
            $month = (clone $now)->subMonths($i);
            $yearMonth = $month->format('Y-m');
            $label = $month->translatedFormat('M Y');

            $totalOut = (int) Transaction::where('item_id', $item->id)
                ->where('type', 'out')
                ->whereYear('transaction_date', $month->year)
                ->whereMonth('transaction_date', $month->month)
                ->sum('quantity');

            $history[] = [
                'period' => $yearMonth,
                'label' => $label,
                'outbound_qty' => $totalOut,
            ];
        }

        // Hitung Moving Average untuk setiap titik data setelah window $windowMonths
        $movingAverages = [];
        $historyCount = count($history);

        for ($i = 0; $i < $historyCount; $i++) {
            if ($i < $windowMonths) {
                // Belum cukup data window untuk moving average
                $movingAverages[] = null;
            } else {
                // Rata-rata dari $windowMonths bulan sebelumnya
                $sum = 0;
                for ($j = 1; $j <= $windowMonths; $j++) {
                    $sum += $history[$i - $j]['outbound_qty'];
                }
                $movingAverages[] = round($sum / $windowMonths, 1);
            }
        }

        // Prediksi bulan depan (N+1) berdasarkan $windowMonths bulan terakhir yang ada
        $lastMonthsOut = array_slice(array_column($history, 'outbound_qty'), -$windowMonths);
        $nextMonthForecast = count($lastMonthsOut) > 0 ? round(array_sum($lastMonthsOut) / count($lastMonthsOut), 1) : 0;
        $nextMonthLabel = (clone $now)->addMonth()->translatedFormat('M Y');

        // Rekomendasi restock pengadaan:
        // Jika stok saat ini < perkiraan kebutuhan bulan depan, rekomendasikan beli selisihnya
        $currentStock = $item->stock;
        $recommendedRestock = max(0, ceil($nextMonthForecast - $currentStock));

        return [
            'item' => $item,
            'window_months' => $windowMonths,
            'history_months' => $historyMonths,
            'history' => $history,
            'moving_averages' => $movingAverages,
            'forecast_next_month' => [
                'period_label' => $nextMonthLabel,
                'predicted_demand' => $nextMonthForecast,
                'current_stock' => $currentStock,
                'recommended_restock' => $recommendedRestock,
            ],
        ];
    }
}
