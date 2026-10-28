<?php

namespace App\Http\Controllers;

use App\Events\CheckoutProgressed;
use App\Models\AbandonedCheckout;
use App\Models\Address;
use App\Models\AnalyticsEvent;
use App\Models\VisitorProfile;
use App\Support\Cart;
use App\Support\Shop;
use App\Support\Tracking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TrackingController extends Controller
{
    /** Item fields the browser may report (anything else is dropped). */
    protected const ITEM_FIELDS = ['item_id' => 40, 'item_name' => 200, 'item_category' => 100, 'item_variant' => 150, 'item_list_name' => 150, 'item_brand' => 100];

    protected const PARAM_FIELDS = ['item_list_name' => 150, 'item_list_id' => 100, 'shipping_tier' => 100, 'payment_type' => 40, 'coupon' => 50,
        'promotion_id' => 100, 'promotion_name' => 150, 'creative_name' => 150, 'method' => 40, 'content_type' => 40, 'item_id' => 40];

    /** Browser-only interactions (list impressions, clicks, checkout steps), batched by toysland-tracker. */
    public function collect(Request $request)
    {
        $data = $request->validate([
            'events' => ['required', 'array', 'max:20'],
            'events.*.name' => ['required', Rule::in(Tracking::BROWSER_EVENTS)],
            'events.*.event_id' => ['required', 'uuid'],
            'events.*.params' => ['nullable', 'array'],
            'events.*.url' => ['nullable', 'string', 'max:1000'],
        ]);

        $rate = (float) Shop::country()->rate ?: 1;
        $seen = AnalyticsEvent::whereIn('event_id', array_column($data['events'], 'event_id'))->pluck('event_id')->all();

        foreach ($data['events'] as $event) {
            if (in_array(strtolower($event['event_id']), $seen, true)) {
                continue; // retried beacon
            }
            $seen[] = strtolower($event['event_id']);
            $params = $this->cleanParams($event['params'] ?? []);
            $items = $params['items'] ?? [];
            $url = $this->sameSiteUrl($event['url'] ?? null);

            Tracking::track($event['name'], $params, [
                'event_id' => $event['event_id'],
                'source' => 'browser',
                'url' => $url,
                'path' => $url ? '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/') : null,
                'product_id' => count($items) === 1 && ctype_digit((string) $items[0]['item_id']) ? (int) $items[0]['item_id'] : null,
                'value_kwd' => isset($params['value']) ? round($params['value'] / $rate, 3) : null,
                'cart' => in_array($event['name'], ['add_shipping_info', 'add_payment_info'], true),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Live abandoned-checkout capture: called when the shopper fills the phone / email (and later
     * steps) on the checkout page, before the order is placed.
     */
    public function checkoutProgress(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'max:150'],
            'country' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:100'],
            'block' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:150'],
            'avenue' => ['nullable', 'string', 'max:100'],
            'property_type' => ['nullable', Rule::in(array_keys(Address::TYPES))],
            'apartment' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'coupon' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', Rule::in(\App\Models\Order::PAYMENT_METHODS)],
            'step' => ['nullable', Rule::in(array_keys(AbandonedCheckout::STEPS))],
        ]);

        $email = Tracking::normalizeEmail($data['email'] ?? null);
        $digits = preg_replace('/\D/', '', (string) ($data['phone'] ?? ''));
        if ((strlen($digits) < 7 && ! $email) || Cart::count() === 0 || Tracking::shouldIgnore()) {
            return response()->json(['ok' => false]);
        }

        $country = Shop::countries()->firstWhere('code', $data['country'] ?? null) ?? Shop::country();
        $phone = strlen($digits) >= 7 ? CheckoutController::fullPhone($country, $data['phone']) : null;
        $visitorId = Tracking::visitorId();
        $items = Cart::items();

        $checkout = AbandonedCheckout::where('visitor_id', $visitorId)->whereIn('status', ['open', 'contacted'])
            ->where('updated_at', '>=', now()->subDay())->latest('id')->first();
        $isNew = $checkout === null;
        $checkout ??= new AbandonedCheckout(['visitor_id' => $visitorId, 'event_id' => (string) Str::uuid(), 'utm_data' => Tracking::attribution() ?: null]);

        $checkout->fill(array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $phone,
            'email' => $email,
            'city' => $data['city'] ?? null,
            'coupon_code' => isset($data['coupon']) ? strtoupper(trim($data['coupon'])) : null,
            'payment_method' => $data['payment_method'] ?? null,
            'gift_message' => $data['notes'] ?? null,
        ], fn ($v) => filled($v)) + [
            'session_id' => session()->getId(),
            'user_id' => auth()->id() ?? $checkout->user_id,
            'country_code' => $country->code,
            'currency' => Tracking::currency($country),
            'items' => $items->map(fn ($line) => [
                'product_id' => $line->product->id,
                'name' => $line->product->name_ar,
                'image' => $line->product->image,
                'options' => Cart::optionsLabel($line->options, 'ar', $line->product),
                'qty' => $line->qty,
                'unit_price' => $line->unit_price,
            ])->values()->all(),
            'cart' => Cart::raw(),
            'items_count' => Cart::count(),
            'subtotal' => Cart::subtotal(),
            'gift_wrap' => $items->flatMap->options->contains(fn ($o) => preg_match('/gift|wrap|تغليف|هدي/iu', $o->name_ar.' '.$o->name_en) === 1),
            'step' => $this->furthestStep($checkout->step, $data['step'] ?? 'contact'),
            'form' => array_intersect_key($data, array_flip(['block', 'street', 'avenue', 'property_type', 'apartment', 'floor', 'notes'])),
        ])->save();

        $identity = ['name' => $checkout->name, 'email' => $checkout->email, 'phone' => $checkout->phone, 'country' => $country->code, 'city' => $checkout->city];
        if ($isNew) {
            Tracking::track('checkout_progress', Tracking::cart(), [
                'event_id' => $checkout->event_id, 'value_kwd' => (float) $checkout->subtotal, 'identity' => $identity, 'cart' => true, 'path' => '/checkout',
            ]);
            CheckoutProgressed::dispatch($checkout);
        } else {
            VisitorProfile::where('visitor_id', $visitorId)->update(array_filter(array_intersect_key($identity, array_flip(['name', 'email', 'phone', 'country']))) + ['last_seen_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    protected function furthestStep(?string $current, string $new): string
    {
        $order = array_keys(AbandonedCheckout::STEPS);

        return array_search($new, $order, true) > array_search($current ?? 'contact', $order, true) ? $new : ($current ?? $new);
    }

    protected function cleanParams(array $params): array
    {
        $out = [];
        foreach (self::PARAM_FIELDS as $key => $max) {
            if (isset($params[$key]) && is_scalar($params[$key])) {
                $out[$key] = mb_substr((string) $params[$key], 0, $max);
            }
        }
        if (isset($params['value']) && is_numeric($params['value'])) {
            $out['value'] = round(min((float) $params['value'], 1_000_000), 3);
        }
        if (isset($params['items']) && is_array($params['items'])) {
            $out['items'] = collect($params['items'])->take(30)->filter(fn ($i) => is_array($i) && isset($i['item_id']) && is_scalar($i['item_id']))
                ->map(function (array $i) {
                    $item = [];
                    foreach (self::ITEM_FIELDS as $key => $max) {
                        if (isset($i[$key]) && is_scalar($i[$key])) {
                            $item[$key] = mb_substr((string) $i[$key], 0, $max);
                        }
                    }
                    foreach (['price', 'discount'] as $key) {
                        if (isset($i[$key]) && is_numeric($i[$key])) {
                            $item[$key] = round((float) $i[$key], 3);
                        }
                    }
                    foreach (['quantity', 'index'] as $key) {
                        if (isset($i[$key]) && is_numeric($i[$key])) {
                            $item[$key] = (int) $i[$key];
                        }
                    }

                    return $item;
                })->values()->all();
        }

        return $out;
    }

    protected function sameSiteUrl(?string $url): ?string
    {
        return $url && parse_url($url, PHP_URL_HOST) === request()->getHost() ? $url : null;
    }
}
