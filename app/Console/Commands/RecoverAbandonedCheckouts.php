<?php

namespace App\Console\Commands;

use App\Models\AbandonedCheckout;
use App\Models\Coupon;
use App\Models\Setting;
use App\Support\WhatsApp;
use Illuminate\Console\Command;
use Throwable;

class RecoverAbandonedCheckouts extends Command
{
    protected $signature = 'checkout:recover';

    protected $description = 'WhatsApp shoppers who left their phone number on the checkout page without ordering';

    public function handle(): int
    {
        $template = config('services.whatsapp.checkout_template');
        if (Setting::get('checkout_recovery_enabled', '0') !== '1' || ! WhatsApp::enabled() || ! $template) {
            return self::SUCCESS;
        }

        $minutes = max(10, (int) Setting::get('checkout_recovery_minutes', 30));
        $coupon = Coupon::promo();
        $sent = 0;

        AbandonedCheckout::where('status', 'open')->whereNull('whatsapp_sent_at')->whereNotNull('phone')
            ->whereBetween('updated_at', [now()->subDay(), now()->subMinutes($minutes)])
            ->each(function (AbandonedCheckout $checkout) use ($template, $coupon, &$sent) {
                if (! ($phone = WhatsApp::normalize($checkout->phone))) {
                    return;
                }
                try {
                    // Template body: {{1}} name, {{2}} link back to the filled checkout, {{3}} promo code.
                    WhatsApp::sendTemplate($phone, $template, [
                        $checkout->name ?: 'عميلنا العزيز',
                        $checkout->restoreUrl(),
                        $coupon ? $coupon->code.' — '.$coupon->discountText('ar') : '—',
                    ]);
                    $checkout->update(['whatsapp_sent_at' => now(), 'status' => 'contacted', 'contacted_at' => now()]);
                    $sent++;
                } catch (Throwable $e) {
                    report($e);
                }
            });

        $this->info("Sent {$sent} checkout recovery message(s).");

        return self::SUCCESS;
    }
}
