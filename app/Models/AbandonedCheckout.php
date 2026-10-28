<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * A checkout where the shopper typed a phone or email but has not (yet) placed the order.
 */
class AbandonedCheckout extends Model
{
    protected $guarded = [];

    protected $casts = [
        'items' => 'array',
        'cart' => 'array',
        'form' => 'array',
        'utm_data' => 'array',
        'subtotal' => 'decimal:3',
        'gift_wrap' => 'boolean',
        'contacted_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public const STATUSES = ['open' => 'متروك', 'contacted' => 'تم التواصل', 'recovered' => 'تم استرجاعه', 'converted' => 'أكمل الطلب'];

    public const STATUS_COLORS = ['open' => 'danger', 'contacted' => 'warning', 'recovered' => 'success', 'converted' => 'gray'];

    public const STEPS = ['contact' => 'بيانات التواصل', 'address' => 'العنوان', 'payment' => 'طريقة الدفع'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(VisitorProfile::class, 'visitor_id', 'visitor_id');
    }

    /** Signed link that refills the cart and the checkout form, valid for 14 days. */
    public function restoreUrl(): string
    {
        return URL::temporarySignedRoute('checkout.restore', now()->addDays(14), ['checkout' => $this->id]);
    }

    public function whatsappUrl(?string $message = null): ?string
    {
        $phone = \App\Support\WhatsApp::normalize($this->phone);
        if (! $phone) {
            return null;
        }
        $message ??= 'هلا '.($this->name ?: '').' 👋 لاحظنا إنك ما كملت طلبك في تويز لاند 🧸'
            ."\nسلتك محفوظة وتقدر تكمل الطلب من هنا: ".$this->restoreUrl()
            .(($coupon = Coupon::promo()) ? "\nوهدية منا: كود الخصم {$coupon->code} 🎁" : '');

        return 'https://wa.me/'.$phone.'?text='.urlencode($message);
    }

    /** Close every open checkout of this visitor / phone once they order. */
    public static function convert(Order $order): void
    {
        $digits = substr(preg_replace('/\D/', '', (string) $order->phone), -8);
        static::whereIn('status', ['open', 'contacted'])
            ->where('created_at', '>=', now()->subDays(30))
            ->where(fn ($q) => $q->when($order->visitor_id, fn ($q) => $q->where('visitor_id', $order->visitor_id))
                ->when($digits !== '', fn ($q) => $q->orWhere('phone', 'like', '%'.$digits)))
            ->get()
            ->each(fn (self $c) => $c->update([
                'status' => $c->status === 'contacted' || $c->whatsapp_sent_at ? 'recovered' : 'converted',
                'order_id' => $order->id,
                'converted_at' => now(),
            ]));
    }
}
