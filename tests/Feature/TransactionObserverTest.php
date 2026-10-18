<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransactionObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_in_increments_stock(): void
    {
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat']);
        $item = Item::create([
            'category_id' => $category->id,
            'sku' => 'TEST-001',
            'name' => 'Item Test',
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        $trx = Transaction::create([
            'item_id' => $item->id,
            'type' => 'in',
            'quantity' => 5,
            'transaction_date' => now(),
        ]);

        $this->assertEquals(15, $item->fresh()->stock);

        $trx->delete();
        $this->assertEquals(10, $item->fresh()->stock);
    }

    public function test_transaction_out_decrements_stock(): void
    {
        $category = Category::create(['name' => 'Test Cat 2', 'slug' => 'test-cat-2']);
        $item = Item::create([
            'category_id' => $category->id,
            'sku' => 'TEST-002',
            'name' => 'Item Test 2',
            'stock' => 10,
            'unit' => 'pcs',
        ]);

        $trx = Transaction::create([
            'item_id' => $item->id,
            'type' => 'out',
            'quantity' => 4,
            'transaction_date' => now(),
        ]);

        $this->assertEquals(6, $item->fresh()->stock);

        $trx->delete();
        $this->assertEquals(10, $item->fresh()->stock);
    }

    public function test_transaction_out_fails_if_stock_insufficient(): void
    {
        $category = Category::create(['name' => 'Test Cat 3', 'slug' => 'test-cat-3']);
        $item = Item::create([
            'category_id' => $category->id,
            'sku' => 'TEST-003',
            'name' => 'Item Test 3',
            'stock' => 3,
            'unit' => 'pcs',
        ]);

        $this->expectException(ValidationException::class);

        Transaction::create([
            'item_id' => $item->id,
            'type' => 'out',
            'quantity' => 10,
            'transaction_date' => now(),
        ]);
    }
}
