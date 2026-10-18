<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSimulationSeeder extends Seeder
{
    /**
     * Run the simulation seeder.
     * Generates realistic monthly Inbound & Outbound transactions for the past 5 years (60 months).
     */
    public function run(): void
    {
        // Bersihkan transaksi simulasi sebelumnya agar tidak menumpuk ganda
        Transaction::truncate();

        $admin = User::first() ?? User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@sinventory.test',
        ]);

        $catElektronik = Category::firstOrCreate(['slug' => 'elektronik'], ['name' => 'Elektronik']);
        $catATK = Category::firstOrCreate(['slug' => 'atk'], ['name' => 'Alat Tulis Kantor']);

        $itemsData = [
            [
                'sku' => 'ELK-001',
                'name' => 'Laptop Dell Latitude 5420',
                'category_id' => $catElektronik->id,
                'initial_stock' => 15,
                'unit' => 'unit',
                'in_qty' => [10, 16],
                'out_qty' => [5, 12],
            ],
            [
                'sku' => 'ELK-002',
                'name' => 'Monitor LG 24 Inch IPS',
                'category_id' => $catElektronik->id,
                'initial_stock' => 20,
                'unit' => 'unit',
                'in_qty' => [8, 15],
                'out_qty' => [4, 11],
            ],
            [
                'sku' => 'ATK-001',
                'name' => 'Kertas A4 70gr PaperOne',
                'category_id' => $catATK->id,
                'initial_stock' => 50,
                'unit' => 'rim',
                'in_qty' => [40, 65],
                'out_qty' => [30, 55],
            ],
            [
                'sku' => 'ATK-002',
                'name' => 'Pulpen Gel Pilot G2 0.5 Black',
                'category_id' => $catATK->id,
                'initial_stock' => 100,
                'unit' => 'box',
                'in_qty' => [30, 55],
                'out_qty' => [20, 48],
            ],
            [
                'sku' => 'ASUS-V-V14',
                'name' => 'Asus Vivobook V14',
                'category_id' => $catElektronik->id,
                'initial_stock' => 10,
                'unit' => 'unit',
                'in_qty' => [6, 12],
                'out_qty' => [4, 9],
            ],
        ];

        $now = Carbon::now();

        foreach ($itemsData as $data) {
            $item = Item::firstOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'category_id' => $data['category_id'],
                    'stock' => $data['initial_stock'],
                    'unit' => $data['unit'],
                    'purchase_price' => 50000,
                    'selling_price' => 65000,
                ]
            );

            // Reset stok ke initial agar dihitung ulang murni oleh observer selama 60 bulan
            $item->update(['stock' => $data['initial_stock']]);

            // Buat transaksi per bulan selama 5 tahun ke belakang (60 bulan)
            for ($monthAgo = 60; $monthAgo >= 0; $monthAgo--) {
                $monthDate = (clone $now)->subMonths($monthAgo);

                // 1. Inbound Transaction di awal bulan
                $inQty = rand($data['in_qty'][0], $data['in_qty'][1]);
                $inDate = (clone $monthDate)->startOfMonth()->addDays(rand(1, 4));

                Transaction::create([
                    'user_id' => $admin->id,
                    'item_id' => $item->id,
                    'type' => 'in',
                    'quantity' => $inQty,
                    'transaction_date' => $inDate->toDateString(),
                    'notes' => 'Restock pengadaan barang bulanan',
                ]);

                // 2. Outbound Transaction di pertengahan bulan
                $outQty = rand($data['out_qty'][0], $data['out_qty'][1]);
                $outDate = (clone $monthDate)->startOfMonth()->addDays(rand(14, 25));

                $item->refresh();
                if ($item->stock < $outQty) {
                    $outQty = max(1, (int)($item->stock * 0.7));
                }

                if ($outQty > 0) {
                    Transaction::create([
                        'user_id' => $admin->id,
                        'item_id' => $item->id,
                        'type' => 'out',
                        'quantity' => $outQty,
                        'transaction_date' => $outDate->toDateString(),
                        'notes' => 'Distribusi pemakaian divisi operasional kantor',
                    ]);
                }
            }
        }
    }
}
