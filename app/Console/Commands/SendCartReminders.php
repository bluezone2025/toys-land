<?php

namespace App\Console\Commands;

use App\Mail\CartReminder;
use App\Models\SavedCart;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCartReminders extends Command
{
    protected $signature = 'cart:remind';

    protected $description = 'Email signed-in customers who left products in their cart';

    public function handle(): int
    {
        if (Setting::get('cart_reminder_enabled', '1') !== '1') {
            return self::SUCCESS;
        }

        $hours = max(1, (int) Setting::get('cart_reminder_hours', 3));
        $sent = 0;

        SavedCart::with('user')
            ->whereNotNull('user_id')
            ->where('items_count', '>', 0)
            ->whereNull('reminded_at')
            ->whereBetween('updated_at', [now()->subDays(3), now()->subHours($hours)])
            ->each(function (SavedCart $cart) use (&$sent) {
                // Skip customers who already ordered after their last cart change.
                if (! $cart->user?->email || $cart->user->orders()->where('created_at', '>=', $cart->updated_at)->exists()) {
                    return;
                }
                try {
                    Mail::to($cart->user->email)->send(new CartReminder($cart));
                    $cart->forceFill(['reminded_at' => now()])->saveQuietly();
                    $sent++;
                } catch (Throwable $e) {
                    report($e);
                }
            });

        $this->info("Sent {$sent} cart reminder(s).");

        return self::SUCCESS;
    }
}
