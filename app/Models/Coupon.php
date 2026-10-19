<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:3',
        'min_subtotal' => 'decimal:3',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public static function findValid(?string $code, float $subtotal): ?self
    {
        if (! $code) {
            return null;
        }

        $coupon = static::where('code', strtoupper(trim($code)))->where('is_active', true)->first();

        if (! $coupon
            || ($coupon->starts_at && $coupon->starts_at->isFuture())
            || ($coupon->expires_at && $coupon->expires_at->isPast())
            || ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses)
            || $subtotal < (float) $coupon->min_subtotal) {
            return null;
        }

        return $coupon;
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'fixed' ? (float) $this->value : $subtotal * (float) $this->value / 100;

        return round(min($discount, $subtotal), 3);
    }

    public function label(): string
    {
        return $this->type === 'fixed' ? $this->code : $this->code.' ('.rtrim(rtrim((string) $this->value, '0'), '.').'%)';
    }
}
