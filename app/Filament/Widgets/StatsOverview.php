<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Item;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalItems = Item::count();
        $totalCategories = Category::count();

        // Total Nilai Aset Inventaris (Stok * Harga Beli)
        $totalAssetValue = Item::all()->sum(function ($item) {
            return $item->stock * $item->purchase_price;
        });

        // Transaksi Bulan Berjalan
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $inboundThisMonth = Transaction::where('type', 'in')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('quantity');

        $outboundThisMonth = Transaction::where('type', 'out')
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('quantity');

        // Barang dengan stok menipis (<= 5)
        $lowStockCount = Item::where('stock', '<=', 5)->count();

        return [
            Stat::make('Total Barang & Kategori', "{$totalItems} Barang ({$totalCategories} Kategori)")
                ->description('Total Master Data Inventaris')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Total Nilai Aset', 'Rp ' . number_format($totalAssetValue, 0, ',', '.'))
                ->description('Berdasarkan Stok x Harga Beli')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Mutasi Bulan Ini', "Masuk: {$inboundThisMonth} | Keluar: {$outboundThisMonth}")
                ->description('Aktivitas Inbound & Outbound')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color('info'),

            Stat::make('Peringatan Stok Menipis', "{$lowStockCount} Barang")
                ->description($lowStockCount > 0 ? 'Perlu segera dilakukan restock' : 'Semua stok dalam batas aman')
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
