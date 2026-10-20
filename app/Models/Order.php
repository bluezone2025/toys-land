<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'decimal:3',
        'discount' => 'decimal:3',
        'shipping' => 'decimal:3',
        'total' => 'decimal:3',
        'currency_rate' => 'decimal:4',
        'paid_at' => 'datetime',
    ];

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    public const PAYMENT_METHODS = ['knet', 'card', 'applepay', 'cod'];

    public function addressLine(): string
    {
        return Address::format($this->only(['city', 'block', 'street', 'avenue', 'property_type', 'apartment', 'floor']));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'TOY-'.random_int(100000, 999999);
        } while (static::where('number', $number)->exists());

        return $number;
    }

    /** Tracking step 1..4 shown to the customer. */
    public function trackingStep(): int
    {
        return match ($this->status) {
            'processing' => 2,
            'shipped' => 3,
            'delivered' => 4,
            default => 1,
        };
    }

    public function isPaidOnline(): bool
    {
        return $this->payment_method !== 'cod' && $this->payment_status === 'paid';
    }

    public function itemsCount(): int
    {
        return (int) $this->items->sum('qty');
    }
}
