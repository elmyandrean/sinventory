<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Services\ForecastingService;
use Filament\Widgets\ChartWidget;

class DemandForecastingChart extends ChartWidget
{
    protected static ?string $heading = 'Prediksi Kebutuhan Stok (Simple Moving Average)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        $options = [];
        $items = Item::all();

        // Variasi Jendela Hitungan (Window)
        $windows = [
            2 => 'Window: 2 Bulan (Peka)',
            3 => 'Window: 3 Bulan (Standar)',
            6 => 'Window: 6 Bulan (Moderat)',
            12 => 'Window: 12 Bulan (Stabil)',
        ];

        // Variasi Rentang Tampilan Historis (Rentang Waktu)
        $periods = [
            12 => '1 Tahun',
            24 => '2 Tahun',
            36 => '3 Tahun',
            60 => '5 Tahun',
        ];

        // Format key: itemId:windowMonths:historyMonths (contoh: "1:3:12", "1:3:60", "1:6:24")
        foreach ($items as $item) {
            foreach ($windows as $win => $winLabel) {
                foreach ($periods as $hist => $histLabel) {
                    $options["{$item->id}:{$win}:{$hist}"] = "{$item->name} | {$winLabel} | Histori: {$histLabel}";
                }
            }
        }

        return $options;
    }

    protected function parseFilter(): array
    {
        $filterValue = $this->filter;
        $itemId = null;
        $windowMonths = 3;   // default 3 bulan
        $historyMonths = 12; // default 1 tahun (12 bulan)

        if ($filterValue && str_contains($filterValue, ':')) {
            $parts = explode(':', $filterValue);
            $itemId = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : null;
            $windowMonths = isset($parts[1]) && is_numeric($parts[1]) ? max(2, (int) $parts[1]) : 3;
            $historyMonths = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : 12;
        }

        $item = $itemId ? Item::find($itemId) : Item::first();

        return [$item, $windowMonths, $historyMonths];
    }

    protected function getData(): array
    {
        [$item, $windowMonths, $historyMonths] = $this->parseFilter();

        if (! $item) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $service = app(ForecastingService::class);
        $result = $service->calculateMovingAverage($item, $windowMonths, $historyMonths);

        $labels = array_column($result['history'], 'label');
        $actualOutbound = array_column($result['history'], 'outbound_qty');
        $movingAverages = $result['moving_averages'];

        // Tambahkan 1 titik prediksi bulan depan
        $labels[] = $result['forecast_next_month']['period_label'] . ' (Prediksi)';
        $actualOutbound[] = null;
        $movingAverages[] = $result['forecast_next_month']['predicted_demand'];

        return [
            'datasets' => [
                [
                    'label' => "Kebutuhan Aktual ({$item->name})",
                    'data' => $actualOutbound,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.4)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'type' => 'bar',
                ],
                [
                    'label' => "Tren Moving Average (SMA-{$windowMonths} Bulan)",
                    'data' => $movingAverages,
                    'borderColor' => 'rgb(245, 158, 11)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                    'type' => 'line',
                    'tension' => 0.2,
                    'borderDash' => [4, 4],
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getDescription(): ?string
    {
        [$item, $windowMonths, $historyMonths] = $this->parseFilter();

        if (! $item) {
            return null;
        }

        $service = app(ForecastingService::class);
        $result = $service->calculateMovingAverage($item, $windowMonths, $historyMonths);
        $forecast = $result['forecast_next_month'];

        $historyYearsText = match ($historyMonths) {
            12 => '1 Tahun Terakhir (12 Bulan)',
            24 => '2 Tahun Terakhir (24 Bulan)',
            36 => '3 Tahun Terakhir (36 Bulan)',
            60 => '5 Tahun Terakhir (60 Bulan)',
            default => "{$historyMonths} Bulan Terakhir",
        };

        $windowExplanation = match ($windowMonths) {
            2 => '⚡ Jendela 2 Bulan (Sangat Peka): Cepat bereaksi terhadap lonjakan terbaru, namun rentan fluktuasi sesaat.',
            3 => '⚖️ Jendela 3 Bulan (Standar Rekomendasi): Keseimbangan ideal antara kecepatan respon dan kestabilan data.',
            6 => '🛡️ Jendela 6 Bulan (Moderat): Garis tren lebih tenang, meredam fluktuasi jangka pendek.',
            12 => '🏛️ Jendela 12 Bulan (Jangka Panjang): Sangat stabil untuk rencana tahunan, namun lambat merespon perubahan pola kebutuhan terkini.',
            default => "Jendela {$windowMonths} bulan.",
        };

        $restockText = $forecast['recommended_restock'] > 0
            ? "⚠️ Rekomendasi Restock: +{$forecast['recommended_restock']} {$item->unit} agar kebutuhan tercukupi."
            : "✅ Sisa stok saat ini masih mencukupi estimasi kebutuhan.";

        return "📊 Rentang Tampilan: {$historyYearsText} | "
             . "Estimasi Permintaan Bulan Depan ({$forecast['period_label']}): ~{$forecast['predicted_demand']} {$item->unit} | "
             . "Stok Saat Ini: {$forecast['current_stock']} {$item->unit} | {$restockText} \n\n"
             . "Karakter Parameter Window: {$windowExplanation}";
    }

    protected function getType(): string
    {
        return 'line';
    }
}
