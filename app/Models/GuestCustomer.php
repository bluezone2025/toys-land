<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestCustomer extends Model
{
    protected $guarded = [];

    protected $casts = ['total_spent' => 'decimal:3', 'last_order_at' => 'datetime', 'unsubscribed_at' => 'datetime'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'email', 'email')->whereNull('user_id');
    }

    /** Keep the guest buyer list current after each guest order. */
    public static function recordOrder(Order $order): void
    {
        if ($order->user_id || ! $order->email) {
            return;
        }
        $guest = static::firstOrNew(['email' => strtolower($order->email)]);
        $guest->fill([
            'name' => $order->name,
            'phone' => $order->phone,
            'country_code' => $order->country_code,
            'city' => $order->city,
            'address' => tt('قطعة ', 'Block ').$order->block.' - '.$order->apartment,
            'locale' => $order->locale,
            'orders_count' => $guest->orders_count + 1,
            'total_spent' => (float) $guest->total_spent + (float) $order->total,
            'last_order_at' => $order->created_at,
        ])->save();
    }
}
