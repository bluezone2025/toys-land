<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Database copy of a visitor's cart, so it survives the session and can be used for reminders.
 */
class SavedCart extends Model
{
    protected $table = 'carts';

    protected $guarded = [];

    protected $casts = ['items' => 'array', 'subtotal' => 'decimal:3', 'reminded_at' => 'datetime', 'whatsapp_sent_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return \Illuminate\Support\Collection<int, array{product: Product, qty: int}> */
    public function lines()
    {
        $products = Product::whereIn('id', array_column($this->items ?? [], 'product_id'))->get()->keyBy('id');

        return collect($this->items ?? [])
            ->map(fn ($row) => isset($products[$row['product_id']]) ? ['product' => $products[$row['product_id']], 'qty' => (int) $row['qty']] : null)
            ->filter()->values();
    }
}
