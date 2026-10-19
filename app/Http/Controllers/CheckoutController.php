<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlaced;
use App\Models\AnalyticsEvent;
use App\Models\Country;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Cart;
use App\Support\MyFatoorah;
use App\Support\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    public static function shippingFor(Country $country, float $subtotal): float
    {
        $threshold = (float) Setting::get('free_shipping_threshold', 15);

        return ($threshold > 0 && $subtotal >= $threshold) ? 0.0 : (float) $country->shipping_fee;
    }

    public static function enabledMethods(): array
    {
        return array_values(array_filter(Order::PAYMENT_METHODS, fn ($m) => Setting::get("pay_{$m}_enabled", '1') === '1'));
    }

    /** @return array{subtotal: float, discount: float, shipping: float, total: float, coupon: ?Coupon} */
    protected function totals(Country $country, ?string $code): array
    {
        $subtotal = Cart::subtotal();
        $coupon = Coupon::findValid($code, $subtotal);
        $discount = $coupon?->discountFor($subtotal) ?? 0.0;
        $shipping = static::shippingFor($country, $subtotal - $discount);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => round($subtotal - $discount + $shipping, 3),
            'coupon' => $coupon,
        ];
    }

    public function show()
    {
        if (Cart::count() === 0) {
            return redirect()->route('cart');
        }
        AnalyticsEvent::record('begin_checkout', ['value' => Cart::subtotal()]);

        return view('store.checkout', [
            'items' => Cart::items(),
            'subtotal' => Cart::subtotal(),
            'countries' => Shop::countries(),
            'enabledMethods' => static::enabledMethods(),
        ]);
    }

    public function summary(Request $request)
    {
        $country = Shop::countries()->firstWhere('code', $request->input('country')) ?? Shop::country();
        $code = trim((string) $request->input('coupon'));
        $t = $this->totals($country, $code);
        session(['coupon' => $t['coupon']?->code]);

        $fmt = fn ($v) => Shop::price($v);

        return response()->json([
            'subtotal' => $fmt($t['subtotal']),
            'discount' => $fmt($t['discount']),
            'shipping' => $t['shipping'] > 0 ? $fmt($t['shipping']) : __('store.free'),
            'shipping_free' => $t['shipping'] == 0,
            'total' => $fmt($t['total']),
            'coupon' => $t['coupon']?->label(),
            'coupon_message' => $t['coupon']
                ? tt('🎉 تم تفعيل كود الخصم بنجاح!', '🎉 Promo code applied!')
                : ($code !== '' ? tt('الكود غير صالح أو منتهي', 'Invalid or expired code') : ''),
        ]);
    }

    public function store(Request $request, MyFatoorah $gateway)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[\d\s+\-()]{7,20}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'country' => ['required', Rule::in(Shop::countries()->pluck('code'))],
            'city' => ['required', 'string', 'max:100'],
            'block' => ['required', 'string', 'max:50'],
            'property_type' => ['required', Rule::in(['house', 'apartment', 'office'])],
            'apartment' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(static::enabledMethods())],
            'coupon' => ['nullable', 'string', 'max:50'],
        ]);

        $items = Cart::items();
        if ($items->isEmpty()) {
            return redirect()->route('cart');
        }

        $country = Shop::countries()->firstWhere('code', $data['country']);
        $displayCountry = Shop::country();
        $t = $this->totals($country, $data['coupon'] ?? null);
        $online = $data['payment_method'] !== 'cod';

        if ($online && ! $gateway->enabled()) {
            throw ValidationException::withMessages(['payment_method' => tt('الدفع الإلكتروني غير متاح حالياً، اختر الدفع عند الاستلام', 'Online payment is unavailable right now, please choose Cash on Delivery')]);
        }

        $order = DB::transaction(function () use ($data, $items, $t, $displayCountry) {
            // Lock products to avoid overselling.
            $products = Product::whereIn('id', $items->pluck('product.id'))->lockForUpdate()->get()->keyBy('id');
            foreach ($items as $item) {
                if ($products[$item->product->id]->stock < $item->qty) {
                    throw ValidationException::withMessages(['cart' => tt('الكمية المطلوبة من "'.$item->product->name_ar.'" غير متوفرة', 'Requested quantity of "'.$item->product->name_en.'" is not available')]);
                }
            }

            $order = Order::create([
                'number' => Order::generateNumber(),
                'user_id' => auth()->id(),
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'country_code' => $data['country'],
                'city' => $data['city'],
                'block' => $data['block'],
                'property_type' => $data['property_type'],
                'apartment' => $data['apartment'],
                'notes' => $data['notes'] ?? null,
                'payment_method' => $data['payment_method'],
                'subtotal' => $t['subtotal'],
                'discount' => $t['discount'],
                'shipping' => $t['shipping'],
                'total' => $t['total'],
                'coupon_code' => $t['coupon']?->label(),
                'currency_code' => $displayCountry->code,
                'currency_rate' => $displayCountry->rate,
                'locale' => app()->getLocale(),
                'session_id' => session()->getId(),
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item->product->id,
                    'name_ar' => $item->product->name_ar,
                    'name_en' => $item->product->name_en,
                    'image' => $item->product->image,
                    'options_ar' => Cart::optionsLabel($item->options, 'ar'),
                    'options_en' => Cart::optionsLabel($item->options, 'en'),
                    'unit_price' => $item->unit_price,
                    'qty' => $item->qty,
                    'total' => $item->total,
                ]);
                $products[$item->product->id]->decrement('stock', $item->qty);
                $products[$item->product->id]->increment('sales_count', $item->qty);
            }

            $t['coupon']?->increment('used_count');

            return $order;
        });

        session()->push('orders', $order->number);

        if (! $online) {
            return $this->complete($order);
        }

        try {
            return redirect()->away($gateway->createPayment($order));
        } catch (Throwable $e) {
            report($e);
            static::releaseOrder($order);

            return redirect()->route('checkout')->withErrors(['payment_method' => tt('تعذر الاتصال ببوابة الدفع، حاول مرة ثانية', 'Could not reach the payment gateway, please try again')]);
        }
    }

    public function callback(Request $request, MyFatoorah $gateway)
    {
        $paymentId = (string) $request->query('paymentId', $request->query('Id', ''));
        abort_if($paymentId === '', 404);

        try {
            $status = $gateway->status($paymentId);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('checkout')->withErrors(['payment_method' => tt('تعذر التحقق من الدفع', 'Could not verify the payment')]);
        }

        $order = Order::where('number', $status['order_number'])->firstOrFail();

        if ($status['paid']) {
            if ($order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid', 'payment_id' => $paymentId, 'paid_at' => now(), 'status' => 'processing']);
            }

            return $this->complete($order);
        }

        static::releaseOrder($order);

        return redirect()->route('checkout')->withErrors(['payment_method' => tt('لم تتم عملية الدفع، تقدر تحاول مرة ثانية أو تختار طريقة دفع ثانية', 'Payment was not completed. Please try again or choose another method')]);
    }

    /** Return stock for an order whose online payment failed. */
    public static function releaseOrder(Order $order): void
    {
        if ($order->status === 'cancelled') {
            return;
        }
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->qty);
                    $item->product->decrement('sales_count', min($item->qty, $item->product->sales_count));
                }
            }
            $order->update(['payment_status' => 'failed', 'status' => 'cancelled']);
        });
    }

    protected function complete(Order $order)
    {
        Cart::clear();
        session()->forget('coupon');
        AnalyticsEvent::record('purchase', ['order_id' => $order->id, 'value' => $order->total]);

        try {
            $admin = Setting::get('admin_email');
            if ($admin) {
                Mail::to($admin)->send(new OrderPlaced($order, true));
            }
            if ($order->email) {
                Mail::to($order->email)->send(new OrderPlaced($order));
            }
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()->route('invoice.show', $order);
    }
}
