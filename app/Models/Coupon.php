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

    /** The coupon configured for reminders / on-site promos, if it can still be used. */
    public static function promo(): ?self
    {
        $id = Setting::get('promo_coupon_id');

        return $id ? static::findValid(static::find($id)?->code, PHP_FLOAT_MAX) : null;
    }

    public function remainingUses(): ?int
    {
        return $this->max_uses ? max(0, $this->max_uses - $this->used_count) : null;
    }

    /** "باقي 2 استخدام فقط" / "Only 2 uses left" / expiry text, for urgency messages. */
    public function urgencyText(?string $locale = null): ?string
    {
        $ar = ($locale ?? app()->getLocale()) !== 'en';
        if (($left = $this->remainingUses()) !== null) {
            return $ar ? "باقي {$left} استخدام فقط على الكود، لحّق قبل ما يخلص!" : "Only {$left} use(s) left on this code — hurry!";
        }
        if ($this->expires_at) {
            return $ar ? 'الكود ينتهي '.$this->expires_at->locale('ar')->diffForHumans() : 'Code expires '.$this->expires_at->locale('en')->diffForHumans();
        }

        return null;
    }

    public function discountText(?string $locale = null): string
    {
        $ar = ($locale ?? app()->getLocale()) !== 'en';
        $value = rtrim(rtrim(number_format((float) $this->value, 3, '.', ''), '0'), '.');

        return $this->type === 'fixed' ? ($ar ? "خصم {$value} د.ك" : "{$value} KD off") : ($ar ? "خصم {$value}%" : "{$value}% off");
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
