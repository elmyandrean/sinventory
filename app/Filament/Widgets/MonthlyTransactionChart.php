<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyTransactionChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Mutasi Barang (12 Bulan Terakhir)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $now = Carbon::now();
        $labels = [];
        $inboundData = [];
        $outboundData = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = (clone $now)->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            $in = Transaction::where('type', 'in')
                ->whereYear('transaction_date', $month->year)
                ->whereMonth('transaction_date', $month->month)
                ->sum('quantity');

            $out = Transaction::where('type', 'out')
                ->whereYear('transaction_date', $month->year)
                ->whereMonth('transaction_date', $month->month)
                ->sum('quantity');

            $inboundData[] = (int) $in;
            $outboundData[] = (int) $out;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Barang Masuk (Inbound)',
                    'data' => $inboundData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Barang Keluar (Outbound)',
                    'data' => $outboundData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
