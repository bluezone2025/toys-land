<?php

namespace App\Console\Commands;

use App\Mail\CartReminder;
use App\Models\Coupon;
use App\Models\SavedCart;
use App\Models\Setting;
use App\Support\WhatsApp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCartReminders extends Command
{
    protected $signature = 'cart:remind';

    protected $description = 'Email (and WhatsApp, when configured) signed-in customers who left products in their cart';

    public function handle(): int
    {
        if (Setting::get('cart_reminder_enabled', '1') !== '1') {
            return self::SUCCESS;
        }

        $hours = max(1, (int) Setting::get('cart_reminder_hours', 3));
        $whatsapp = WhatsApp::enabled();
        $coupon = Coupon::promo();
        $sent = 0;

        SavedCart::with('user')
            ->whereNotNull('user_id')
            ->where('items_count', '>', 0)
            ->whereNull('reminded_at')
            ->whereBetween('updated_at', [now()->subDays(3), now()->subHours($hours)])
            ->each(function (SavedCart $cart) use (&$sent, $whatsapp, $coupon) {
                $user = $cart->user;
                // Skip customers who already ordered after their last cart change, or opted out.
                if (! $user?->email || $user->unsubscribed_at || $user->orders()->where('created_at', '>=', $cart->updated_at)->exists()) {
                    return;
                }
                try {
                    Mail::to($user->email)->queue(new CartReminder($cart));
                    $cart->forceFill(['reminded_at' => now()])->saveQuietly();
                    $sent++;
                } catch (Throwable $e) {
                    report($e);
                }

                if ($whatsapp && ! $cart->whatsapp_sent_at && ($phone = WhatsApp::normalize($user->phone))) {
                    try {
                        $locale = $user->locale === 'en' ? 'en' : 'ar';
                        WhatsApp::sendTemplate($phone, config('services.whatsapp.cart_template'), [
                            $user->name,
                            $coupon ? $coupon->code.' — '.$coupon->discountText($locale) : '—',
                        ], $locale);
                        $cart->forceFill(['whatsapp_sent_at' => now()])->saveQuietly();
                    } catch (Throwable $e) {
                        report($e);
                    }
                }
            });

        $this->info("Queued {$sent} cart reminder(s).");

        return self::SUCCESS;
    }
}
