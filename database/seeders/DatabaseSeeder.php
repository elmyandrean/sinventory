<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Setup Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@sinventory.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole($adminRole);

        // Sample Category & Item
        $catElektronik = Category::firstOrCreate(
            ['slug' => 'elektronik'],
            ['name' => 'Elektronik', 'description' => 'Perangkat keras & elektronik']
        );

        $catATK = Category::firstOrCreate(
            ['slug' => 'atk'],
            ['name' => 'Alat Tulis Kantor', 'description' => 'Kebutuhan ATK kantor']
        );

        Item::firstOrCreate(
            ['sku' => 'ELK-001'],
            [
                'category_id' => $catElektronik->id,
                'name' => 'Laptop Dell Latitude 5420',
                'stock' => 15,
                'unit' => 'unit',
                'purchase_price' => 12000000,
                'selling_price' => 14500000,
                'description' => 'Laptop inventaris kantor',
            ]
        );

        Item::firstOrCreate(
            ['sku' => 'ATK-001'],
            [
                'category_id' => $catATK->id,
                'name' => 'Kertas A4 70gr PaperOne',
                'stock' => 50,
                'unit' => 'rim',
                'purchase_price' => 45000,
                'selling_price' => 55000,
                'description' => 'Kertas print kantor',
            ]
        );
    }
}
