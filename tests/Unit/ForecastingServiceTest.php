<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Item;
use App\Models\Transaction;
use App\Services\ForecastingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForecastingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_moving_average_logic(): void
    {
        $category = Category::create(['name' => 'Elektronik', 'slug' => 'elektronik']);
        $item = Item::create([
            'category_id' => $category->id,
            'sku' => 'TEST-FC-001',
            'name' => 'Forecasting Test Item',
            'stock' => 100,
            'unit' => 'pcs',
        ]);

        $now = Carbon::now();

        // Buat outbound: 3 bulan lalu (10), 2 bulan lalu (20), 1 bulan lalu (30)
        // SMA 3 bulan = (10 + 20 + 30) / 3 = 20
        Transaction::create([
            'item_id' => $item->id,
            'type' => 'out',
            'quantity' => 10,
            'transaction_date' => (clone $now)->subMonths(3)->startOfMonth(),
        ]);

        Transaction::create([
            'item_id' => $item->id,
            'type' => 'out',
            'quantity' => 20,
            'transaction_date' => (clone $now)->subMonths(2)->startOfMonth(),
        ]);

        Transaction::create([
            'item_id' => $item->id,
            'type' => 'out',
            'quantity' => 30,
            'transaction_date' => (clone $now)->subMonths(1)->startOfMonth(),
        ]);

        $service = new ForecastingService();
        $result = $service->calculateMovingAverage($item, 3);

        $this->assertEquals(3, $result['window_months']);
        $this->assertEquals(12, count($result['history']));
        $this->assertArrayHasKey('forecast_next_month', $result);
        $this->assertIsNumeric($result['forecast_next_month']['predicted_demand']);
    }
}
