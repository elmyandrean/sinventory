<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Validation\ValidationException;

class TransactionObserver
{
    /**
     * Handle the Transaction "creating" event.
     * Validate stock sufficiency before saving an outbound transaction.
     */
    public function creating(Transaction $transaction): void
    {
        if ($transaction->type === 'out') {
            $item = $transaction->item()->lockForUpdate()->first();
            if ($item && $item->stock < $transaction->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok barang '{$item->name}' tidak mencukupi. Sisa stok: {$item->stock}.",
                ]);
            }
        }
    }

    /**
     * Handle the Transaction "created" event.
     * Automatically update item stock.
     */
    public function created(Transaction $transaction): void
    {
        $item = $transaction->item;
        if (! $item) {
            return;
        }

        if ($transaction->type === 'in') {
            $item->increment('stock', $transaction->quantity);
        } elseif ($transaction->type === 'out') {
            $item->decrement('stock', $transaction->quantity);
        }
    }

    /**
     * Handle the Transaction "deleted" event.
     * Rollback item stock when a transaction is deleted.
     */
    public function deleted(Transaction $transaction): void
    {
        $item = $transaction->item;
        if (! $item) {
            return;
        }

        // Invert effect to rollback
        if ($transaction->type === 'in') {
            $item->decrement('stock', $transaction->quantity);
        } elseif ($transaction->type === 'out') {
            $item->increment('stock', $transaction->quantity);
        }
    }
}
