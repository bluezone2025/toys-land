<?php

namespace App\Support;

use App\Models\AnalyticsEvent;
use App\Models\Country;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\VisitorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Hybrid (browser + server) e-commerce tracking.
 *
 * Every interaction gets one event_id (UUID v4). The same id goes to the page's dataLayer /
 * browser pixels and to the server-side copy queued for Meta CAPI, TikTok Events API,
 * Snapchat CAPI and GA4 Measurement Protocol, so the platforms count it once. Every event is
 * also stored in `analytics_events` against a first-party visitor id, which powers the admin
 * journey timeline, live feed, funnel and attribution reports.
 */
class Tracking
{
    public const VISITOR_COOKIE = 'tl_vid';

    public const ATTRIBUTION_COOKIE = 'tl_attr';

    public const CONSENT_COOKIE = 'tl_consent';

    /** Written by browser pixels (or for them); read as-is, never encrypted. */
    public const PLAIN_COOKIES = ['_fbp', '_fbc', '_ttp', '_scid', '_ga', self::CONSENT_COOKIE];

    public const UTM_PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    public const CLICK_IDS = ['fbclid' => 'facebook', 'ttclid' => 'tiktok', 'sccid' => 'snapchat', 'ScCid' => 'snapchat', 'gclid' => 'google', 'gbraid' => 'google', 'wbraid' => 'google'];

    /** GA4 event => [Meta, TikTok, Snapchat] standard event names sent server-side. */
    public const PLATFORM_EVENTS = [
        'view_item' => ['ViewContent', 'ViewContent', 'VIEW_CONTENT'],
        'add_to_cart' => ['AddToCart', 'AddToCart', 'ADD_CART'],
        'add_to_wishlist' => ['AddToWishlist', 'AddToWishlist', 'ADD_TO_WISHLIST'],
        'begin_checkout' => ['InitiateCheckout', 'InitiateCheckout', 'START_CHECKOUT'],
        'add_payment_info' => ['AddPaymentInfo', 'AddPaymentInfo', 'ADD_BILLING'],
        'purchase' => ['Purchase', 'CompletePayment', 'PURCHASE'],
        'search' => ['Search', 'Search', 'SEARCH'],
    ];

    /** Events the browser may report through the collector endpoint. */
    public const BROWSER_EVENTS = ['view_item_list', 'select_item', 'add_shipping_info', 'add_payment_info', 'select_promotion', 'share'];

    /** Arabic labels for the admin. */
    public const LABELS = [
        'page_view' => 'زار صفحة',
        'view_item_list' => 'شاف قائمة منتجات',
        'select_item' => 'ضغط على منتج',
        'view_item' => 'شاف منتج',
        'search' => 'بحث',
        'add_to_wishlist' => 'أضاف للمفضلة',
        'add_to_cart' => 'أضاف للسلة',
        'remove_from_cart' => 'حذف من السلة',
        'view_cart' => 'فتح السلة',
        'begin_checkout' => 'بدأ الدفع',
        'checkout_progress' => 'كتب بيانات التواصل',
        'add_shipping_info' => 'اختار العنوان',
        'add_payment_info' => 'اختار طريقة الدفع',
        'purchase' => 'اشترى',
        'checkout_restored' => 'رجع من رابط الاسترجاع',
        'select_promotion' => 'ضغط على عرض',
        'share' => 'شارك',
    ];

    public const ICONS = [
        'page_view' => '👀', 'view_item_list' => '🗂️', 'select_item' => '👆', 'view_item' => '🧸', 'search' => '🔍',
        'add_to_wishlist' => '❤️', 'add_to_cart' => '🛒', 'remove_from_cart' => '🗑️', 'view_cart' => '🧺',
        'begin_checkout' => '💳', 'checkout_progress' => '📱', 'add_shipping_info' => '🚚', 'add_payment_info' => '💰',
        'purchase' => '🎉', 'checkout_restored' => '🔁', 'select_promotion' => '🏷️', 'share' => '📤',
    ];

    /** Buying-intent points per event, and the funnel stage it proves (see VisitorProfile::STAGES). */
    protected const INTENT = [
        'page_view' => [1, 0], 'view_item_list' => [0, 0], 'select_item' => [1, 1], 'view_item' => [2, 1], 'search' => [2, 1],
        'add_to_wishlist' => [4, 1], 'add_to_cart' => [8, 2], 'view_cart' => [2, 2], 'remove_from_cart' => [0, 2],
        'begin_checkout' => [10, 3], 'add_shipping_info' => [6, 3], 'checkout_progress' => [12, 3], 'add_payment_info' => [8, 3],
        'checkout_restored' => [6, 3], 'purchase' => [30, 4],
    ];

    protected const CURRENCIES = ['KW' => 'KWD', 'SA' => 'SAR', 'AE' => 'AED', 'QA' => 'QAR', 'BH' => 'BHD', 'OM' => 'OMR'];

    protected const DIAL_CODES = ['KW' => '965', 'SA' => '966', 'AE' => '971', 'QA' => '974', 'BH' => '973', 'OM' => '968'];

    // ---------------------------------------------------------------- identity & attribution

    public static function validUuid(mixed $value): ?string
    {
        return is_string($value) && Str::isUuid($value) ? strtolower($value) : null;
    }

    public static function visitorId(): string
    {
        $request = request();
        $id = $request->attributes->get(self::VISITOR_COOKIE) ?? static::validUuid($request->cookie(self::VISITOR_COOKIE));
        if (! $id) {
            $id = (string) Str::uuid();
            $request->attributes->set(self::VISITOR_COOKIE, $id);
        }

        return $id;
    }

    /** @return array{first?: array, last?: array} */
    public static function attribution(): array
    {
        $request = request();
        if ($request->attributes->has(self::ATTRIBUTION_COOKIE)) {
            return $request->attributes->get(self::ATTRIBUTION_COOKIE);
        }
        $data = json_decode((string) $request->cookie(self::ATTRIBUTION_COOKIE), true);

        return is_array($data) ? $data : [];
    }

    /**
     * The marketing touch carried by this request: UTM tags and ad click ids, or else an
     * external referrer. Null for direct / internal navigation so it never overwrites a real source.
     */
    public static function touchFrom(Request $request): ?array
    {
        $touch = [];
        foreach (self::UTM_PARAMS as $key) {
            if (filled($v = $request->query($key)) && is_string($v)) {
                $touch[substr($key, 4)] = mb_substr($v, 0, 150);
            }
        }
        foreach (self::CLICK_IDS as $key => $network) {
            if (filled($v = $request->query($key)) && is_string($v)) {
                $touch[strtolower($key)] = mb_substr($v, 0, 500);
                $touch['source'] ??= $network;
                $touch['medium'] ??= 'paid';
            }
        }

        $referrer = $request->headers->get('referer');
        $host = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : '';
        $host = preg_replace('/^(www|m|l|lm)\./', '', $host);
        if (! $touch && $host !== '' && $host !== preg_replace('/^www\./', '', $request->getHost())) {
            $search = (bool) preg_match('/(^|\.)(google|bing|yahoo|duckduckgo|yandex|ecosia)\./', $host);
            $social = (bool) preg_match('/(facebook|instagram|tiktok|snapchat|t\.co|twitter|x\.com|youtube|whatsapp|wa\.me|linkedin|pinterest|threads)/', $host);
            $touch = ['source' => $host, 'medium' => $search ? 'organic' : ($social ? 'social' : 'referral')];
        }
        if (! $touch) {
            return null;
        }

        return $touch + ['landing' => '/'.ltrim(mb_substr($request->path(), 0, 300), '/'), 'at' => now()->timestamp];
    }

    public static function consentGranted(?string $choice = null): bool
    {
        if (Setting::get('consent_banner', '1') !== '1') {
            return true;
        }
        $choice ??= request()->cookie(self::CONSENT_COOKIE);

        return match ($choice) {
            'granted' => true,
            'denied' => false,
            default => Setting::get('consent_default', 'granted') !== 'denied',
        };
    }

    /** Network details the ad platforms use to match a server event to a person. */
    public static function clientContext(?Request $request = null): array
    {
        $request ??= request();
        $ip = $request->header('CF-Connecting-IP')
            ?: trim(explode(',', (string) $request->header('X-Forwarded-For'))[0] ?? '')
            ?: $request->ip();
        $attr = static::attribution();
        $last = $attr['last'] ?? [];
        $fbc = $request->cookie('_fbc') ?: (isset($last['fbclid']) ? 'fb.1.'.(($last['at'] ?? time()) * 1000).'.'.$last['fbclid'] : null);

        return array_filter([
            'ip' => filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $request->ip(),
            'ua' => mb_substr((string) $request->userAgent(), 0, 400),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $fbc,
            'ttp' => $request->cookie('_ttp'),
            'ttclid' => $last['ttclid'] ?? null,
            'scid' => $request->cookie('_scid'),
            'sccid' => $last['sccid'] ?? null,
            'ga' => static::gaClientId($request->cookie('_ga')),
            'gclid' => $last['gclid'] ?? null,
            'consent' => static::consentGranted() ? 'granted' : 'denied',
        ], fn ($v) => filled($v));
    }

    /** "GA1.1.123456789.1700000000" → "123456789.1700000000". */
    public static function gaClientId(?string $cookie): ?string
    {
        $parts = explode('.', (string) $cookie);

        return count($parts) >= 4 ? $parts[count($parts) - 2].'.'.$parts[count($parts) - 1] : null;
    }

    // ---------------------------------------------------------------- money & items

    public static function currency(?Country $country = null): string
    {
        $code = ($country ?? Shop::country())->code;

        return self::CURRENCIES[$code] ?? 'KWD';
    }

    /** KWD amount in the visitor's currency (what they see on the page). */
    public static function convert(float|string|null $kwd, ?Country $country = null): float
    {
        return round((float) $kwd * (float) ($country ?? Shop::country())->rate, 3);
    }

    /** GA4 item for a product (price may include chosen options). */
    public static function item(Product $product, int $qty = 1, ?float $unitKwd = null, array $extra = [], ?Country $country = null): array
    {
        $price = (float) $product->price;
        $item = [
            'item_id' => (string) $product->id,
            'item_name' => $product->tr('name'),
            'item_brand' => (string) Setting::tr('store_name', 'Toys Land'),
            'price' => static::convert($unitKwd ?? $price, $country),
            'discount' => (float) $product->compare_price > $price ? static::convert((float) $product->compare_price - $price, $country) : 0.0,
            'quantity' => max(1, $qty),
        ];
        foreach ($product->categories->take(3)->values() as $i => $category) {
            $item[$i === 0 ? 'item_category' : 'item_category'.($i + 1)] = $category->tr('title');
        }
        if ($product->ageGroups->isNotEmpty()) {
            $item['item_variant'] = $product->ageGroups->pluck('key')->implode(', ').' '.tt('سنوات', 'Years');
        }

        return $item + $extra;
    }

    /** @return array{value: float, items: array} for the current cart */
    public static function cart(): array
    {
        $items = Cart::items();

        return [
            'value' => static::convert(Cart::subtotal()),
            'items' => $items->values()->map(fn ($line, $i) => static::item($line->product, $line->qty, $line->unit_price, ['index' => $i]
                + (($label = Cart::optionsLabel($line->options, app()->getLocale(), $line->product)) !== '' ? ['item_variant' => $label] : [])))->all(),
        ];
    }

    public static function orderParams(Order $order): array
    {
        $order->loadMissing('items.product.categories', 'items.product.ageGroups');
        $country = new Country(['code' => $order->currency_code, 'rate' => $order->currency_rate ?: 1]);

        return [
            'transaction_id' => $order->number,
            'value' => static::convert($order->total, $country),
            'currency' => self::CURRENCIES[$order->currency_code] ?? 'KWD',
            'shipping' => static::convert($order->shipping, $country),
            'tax' => 0,
            'discount' => static::convert($order->discount, $country),
            'coupon' => $order->coupon_code ? strtok($order->coupon_code, ' ') : null,
            'payment_type' => static::paymentType($order->payment_method),
            'items' => $order->items->values()->map(fn ($line, $i) => $line->product
                ? static::item($line->product, $line->qty, (float) $line->unit_price, ['index' => $i] + ($line->options_en ? ['item_variant' => $line->options_en] : []), $country)
                : ['item_id' => (string) $line->product_id, 'item_name' => $line->name_ar, 'price' => static::convert($line->unit_price, $country), 'quantity' => $line->qty, 'index' => $i]
            )->all(),
        ];
    }

    public static function paymentType(?string $method): string
    {
        return ['knet' => 'KNET', 'card' => 'Credit Card', 'applepay' => 'Apple Pay', 'cod' => 'COD'][$method] ?? (string) $method;
    }

    // ---------------------------------------------------------------- recording

    /** Bots, link previews, prefetches and the store's own admins are not shoppers. */
    public static function shouldIgnore(): bool
    {
        $request = request();

        return UserAgent::isBot((string) $request->userAgent())
            || in_array(strtolower((string) $request->header('Sec-Purpose', $request->header('Purpose'))), ['prefetch', 'prefetch;prerender'], true)
            || (bool) auth()->user()?->is_admin;
    }

    /**
     * Record an event and return its dataLayer payload (null when ignored).
     *
     * $opts: event_id, source (server|browser), product_id, order_id, value_kwd, path, url,
     *        visitor_id, session_id, user_id, client (stored client context), consent,
     *        identity (name/email/phone/country/city), cart (refresh profile cart), force,
     *        profile (false: do not touch the visitor profile).
     */
    public static function track(string $name, array $params = [], array $opts = []): ?array
    {
        if (! ($opts['force'] ?? false) && static::shouldIgnore()) {
            return null;
        }

        $eventId = static::validUuid($opts['event_id'] ?? null) ?? (string) Str::uuid();
        $params = ['currency' => $params['currency'] ?? static::currency()] + $params;
        $visitorId = $opts['visitor_id'] ?? static::visitorId();
        $consent = static::consentGranted($opts['consent'] ?? ($opts['client']['consent'] ?? null));
        $send = isset(self::PLATFORM_EVENTS[$name]) && static::serverPlatforms() !== [] && $consent;

        try {
            AnalyticsEvent::create([
                'type' => $name,
                'event_id' => $eventId,
                'visitor_id' => $visitorId,
                'session_id' => $opts['session_id'] ?? session()->getId(),
                'user_id' => $opts['user_id'] ?? auth()->id(),
                'product_id' => $opts['product_id'] ?? null,
                'order_id' => $opts['order_id'] ?? null,
                'value' => $opts['value_kwd'] ?? null,
                'meta' => static::compact($params),
                'source' => $opts['source'] ?? 'server',
                'path' => mb_substr($opts['path'] ?? '/'.ltrim(request()->path(), '/'), 0, 500),
                'dispatch_status' => $send ? 'pending' : null,
                'capi' => $send ? static::serverPayload($name, $eventId, $params, $opts, $visitorId) : null,
                'created_at' => now(),
            ]);
            if ($opts['profile'] ?? true) {
                static::touchProfile($visitorId, $name, $opts);
            }
        } catch (Throwable $e) {
            report($e);
        }

        return static::payload($name, $eventId, $params);
    }

    /** dataLayer object in the shape of the GA4 e-commerce schema. */
    public static function payload(string $name, string $eventId, array $params): array
    {
        $out = ['event' => $name, 'event_id' => $eventId, 'currency' => $params['currency'] ?? static::currency(), 'store_country' => Shop::country()->code];
        if (array_key_exists('search_term', $params)) {
            $out['search_term'] = $params['search_term'];
            unset($params['search_term']);
        }

        return $out + ['ecommerce' => $params];
    }

    /** Small copy of the params for the admin (items reduced to what the timeline shows). */
    protected static function compact(array $params): array
    {
        if (isset($params['items'])) {
            $params['items'] = array_map(fn ($i) => array_intersect_key($i, array_flip(['item_id', 'item_name', 'price', 'quantity', 'item_variant'])), array_slice($params['items'], 0, 30));
        }

        return $params;
    }

    /** dataLayer push for the page being rendered (kept on the request, never shared between requests). */
    public static function push(?array $payload): void
    {
        if ($payload) {
            request()->attributes->set('tl_events', [...request()->attributes->get('tl_events', []), $payload]);
        }
    }

    /** For actions that redirect (cart forms): the next page pushes it. */
    public static function flash(?array $payload): void
    {
        if ($payload) {
            session()->flash('tl_events', [...session('tl_events', []), $payload]);
        }
    }

    public static function pageEvents(): array
    {
        return [...session('tl_events', []), ...request()->attributes->get('tl_events', [])];
    }

    /** Server-side purchase, sent once per order however many callbacks / webhooks arrive. */
    public static function purchase(Order $order): void
    {
        if (! Order::whereKey($order->id)->whereNull('tracking_sent_at')->update(['tracking_sent_at' => now()])) {
            return;
        }
        $client = $order->client_context ?? [];
        [$first, $last] = static::splitName($order->name);

        static::track('purchase', static::orderParams($order), [
            'force' => true,
            'event_id' => $order->event_id,
            'visitor_id' => $order->visitor_id ?? static::visitorId(),
            'session_id' => $order->session_id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'value_kwd' => (float) $order->total,
            'path' => '/invoice/'.$order->number,
            'url' => route('invoice.show', $order),
            'client' => $client,
            'identity' => ['name' => $order->name, 'email' => $order->email, 'phone' => $order->phone, 'country' => $order->country_code, 'city' => $order->city, 'user_id' => $order->user_id],
            'first_name' => $first,
            'last_name' => $last,
            'order_total' => (float) $order->total,
        ]);
    }

    /** Browser copy of the purchase for the invoice page, once, with the order's event_id. */
    public static function browserPurchase(Order $order): ?array
    {
        $isSale = $order->payment_method === 'cod' || $order->payment_status === 'paid';
        if (! $isSale || $order->status === 'cancelled' || auth()->user()?->is_admin || ! $order->event_id
            || ! Order::whereKey($order->id)->whereNull('browser_tracked_at')->update(['browser_tracked_at' => now()])) {
            return null;
        }

        return static::payload('purchase', $order->event_id, static::orderParams($order));
    }

    // ---------------------------------------------------------------- visitor profile

    public static function touchProfile(string $visitorId, string $event, array $opts = []): void
    {
        $profile = VisitorProfile::firstOrNew(['visitor_id' => $visitorId]);
        [$points, $stage] = self::INTENT[$event] ?? [1, 0];
        $now = now();

        if (! $profile->exists) {
            $profile->first_seen_at = $now;
        }
        // Old interest fades: halve the score after a week away.
        $score = $profile->last_seen_at && $profile->last_seen_at->lt($now->copy()->subWeek()) ? intdiv((int) $profile->intent_score, 2) : (int) $profile->intent_score;
        $profile->fill([
            'intent_score' => min(100, $score + $points),
            'stage' => max((int) $profile->stage, $stage),
            'last_seen_at' => $now,
            'events_count' => (int) $profile->events_count + ($event === 'page_view' ? 0 : 1),
            'last_event' => $event,
        ]);
        if ($event === 'view_item') {
            $profile->products_viewed = (int) $profile->products_viewed + 1;
        }
        if ($userId = $opts['user_id'] ?? auth()->id()) {
            $profile->user_id = $userId;
        }

        $identity = array_filter($opts['identity'] ?? []);
        foreach (['name', 'email', 'phone', 'country'] as $key) {
            if (isset($identity[$key])) {
                $profile->{$key} = mb_substr($key === 'email' ? strtolower($identity[$key]) : $identity[$key], 0, $key === 'country' ? 2 : 190);
            }
        }
        if (! empty($identity['user_id'])) {
            $profile->user_id = $identity['user_id'];
        }

        if ($event === 'purchase') {
            $profile->orders_count = (int) $profile->orders_count + 1;
            $profile->total_spent = (float) $profile->total_spent + (float) ($opts['order_total'] ?? 0);
            $profile->cart_value = 0;
            $profile->cart_items = 0;
        } elseif ($opts['cart'] ?? false) {
            $profile->cart_value = Cart::subtotal();
            $profile->cart_items = Cart::count();
        }

        if (isset($opts['visit'])) {
            static::applyVisit($profile, $opts['visit']);
        }

        $profile->save();
    }

    protected static function applyVisit(VisitorProfile $profile, \App\Models\Visit $visit): void
    {
        $attr = static::attribution();
        $first = $attr['first'] ?? [];
        $last = $attr['last'] ?? [];

        if ($profile->last_session_id !== $visit->session_id) {
            $profile->sessions_count = (int) $profile->sessions_count + 1;
            $profile->last_session_id = $visit->session_id;
        }
        $profile->page_views = (int) $profile->page_views + 1;
        $profile->last_path = $visit->path;
        $profile->fill(array_filter([
            'device' => $visit->device, 'browser' => $visit->browser, 'platform' => $visit->platform, 'locale' => $visit->locale,
            'country' => $profile->country ?: $visit->country,
            'last_source' => $last['source'] ?? null, 'last_medium' => $last['medium'] ?? null, 'last_campaign' => $last['campaign'] ?? null,
        ]));
        if (! $profile->landing_page) {
            $profile->fill([
                'landing_page' => $first['landing'] ?? $visit->path,
                'first_source' => $first['source'] ?? null,
                'first_medium' => $first['medium'] ?? null,
                'first_campaign' => $first['campaign'] ?? null,
                'first_referrer' => $visit->referrer,
            ]);
        }
    }

    // ---------------------------------------------------------------- server-side payload

    /** Configured server-side destinations: platform => credentials. */
    public static function serverPlatforms(): array
    {
        if (Setting::get('server_tracking_enabled', '1') !== '1') {
            return [];
        }
        $get = fn (string $setting, string $config) => Setting::get($setting) ?: config("services.{$config}");

        return array_filter([
            'meta' => ($id = $get('facebook_pixel_id', 'meta.pixel_id')) && ($token = $get('meta_capi_token', 'meta.capi_token'))
                ? ['pixel_id' => $id, 'token' => $token, 'test_code' => $get('meta_test_event_code', 'meta.test_event_code')] : null,
            'tiktok' => ($id = $get('tiktok_pixel_id', 'tiktok.pixel_id')) && ($token = $get('tiktok_access_token', 'tiktok.access_token'))
                ? ['pixel_id' => $id, 'token' => $token, 'test_code' => $get('tiktok_test_event_code', 'tiktok.test_event_code')] : null,
            'snap' => ($id = $get('snap_pixel_id', 'snap.pixel_id')) && ($token = $get('snap_capi_token', 'snap.capi_token'))
                ? ['pixel_id' => $id, 'token' => $token] : null,
            'ga4' => ($id = $get('ga4_id', 'ga4.measurement_id')) && ($secret = $get('ga4_api_secret', 'ga4.api_secret'))
                ? ['measurement_id' => $id, 'secret' => $secret] : null,
        ]);
    }

    /** Browser pixel ids for the page (only public ids, never tokens). */
    public static function browserPixels(): array
    {
        $get = fn (string $setting, string $config) => Setting::get($setting) ?: config("services.{$config}");

        return array_filter([
            'gtm' => $get('gtm_id', 'gtm.container_id'),
            'ga4' => $get('ga4_id', 'ga4.measurement_id'),
            'meta' => $get('facebook_pixel_id', 'meta.pixel_id'),
            'tiktok' => $get('tiktok_pixel_id', 'tiktok.pixel_id'),
            'snap' => $get('snap_pixel_id', 'snap.pixel_id'),
        ]);
    }

    protected static function serverPayload(string $name, string $eventId, array $params, array $opts, string $visitorId): array
    {
        $client = $opts['client'] ?? static::clientContext();
        $identity = $opts['identity'] ?? [];
        $user = auth()->user();
        if (! $identity && $user && ! $user->is_admin) {
            $identity = ['email' => $user->email, 'phone' => $user->phone];
        }
        [$first, $last] = isset($opts['first_name']) ? [$opts['first_name'], $opts['last_name'] ?? null] : static::splitName($identity['name'] ?? null);
        $country = $identity['country'] ?? Shop::country()->code;

        return [
            'name' => $name,
            'event_id' => $eventId,
            'time' => now()->timestamp,
            'url' => mb_substr($opts['url'] ?? request()->fullUrl(), 0, 1000),
            'currency' => $params['currency'],
            'value' => isset($params['value']) ? (float) $params['value'] : null,
            'order_id' => $params['transaction_id'] ?? null,
            'shipping' => $params['shipping'] ?? null,
            'coupon' => $params['coupon'] ?? null,
            'search' => $params['search_term'] ?? null,
            'items' => array_map(fn ($i) => [
                'id' => (string) $i['item_id'], 'name' => $i['item_name'] ?? null, 'category' => $i['item_category'] ?? null,
                'variant' => $i['item_variant'] ?? null, 'price' => (float) ($i['price'] ?? 0), 'quantity' => (int) ($i['quantity'] ?? 1),
            ], array_slice($params['items'] ?? [], 0, 50)),
            'user' => array_filter([
                'em' => static::hash(static::normalizeEmail($identity['email'] ?? null)),
                'ph' => static::hash(static::normalizePhone($identity['phone'] ?? null, $country)),
                'fn' => static::hash(static::normalizeText($first)),
                'ln' => static::hash(static::normalizeText($last)),
                'ct' => static::hash(static::normalizeText($identity['city'] ?? null, true)),
                'country' => static::hash(strtolower((string) $country)),
                'external_id' => static::hash($visitorId),
                'ip' => $client['ip'] ?? null,
                'ua' => $client['ua'] ?? null,
                'fbp' => $client['fbp'] ?? null,
                'fbc' => $client['fbc'] ?? null,
                'ttp' => $client['ttp'] ?? null,
                'ttclid' => $client['ttclid'] ?? null,
                'scid' => $client['scid'] ?? null,
                'sccid' => $client['sccid'] ?? null,
                'ga' => $client['ga'] ?? null,
                'gclid' => $client['gclid'] ?? null,
                'user_id' => $opts['user_id'] ?? auth()->id(),
            ], fn ($v) => filled($v)),
        ];
    }

    // ---------------------------------------------------------------- PII normalisation

    public static function hash(?string $value): ?string
    {
        return filled($value) ? hash('sha256', $value) : null;
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /** Digits with the GCC country code: "0099 9999" in Kuwait → "96599999999". */
    public static function normalizePhone(?string $phone, ?string $countryCode = 'KW'): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        $digits = ltrim($digits, '0');
        $dial = Shop::countries()->firstWhere('code', $countryCode)?->dial_code ?? self::DIAL_CODES[$countryCode] ?? '965';
        if (! (str_starts_with($digits, $dial) && strlen($digits) > strlen($dial) + 6)) {
            $digits = $dial.$digits;
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }

    protected static function normalizeText(?string $value, bool $stripSpaces = false): ?string
    {
        $value = mb_strtolower(trim((string) $value));
        if ($stripSpaces) {
            $value = preg_replace('/[\s\p{P}]+/u', '', $value);
        }

        return $value !== '' ? $value : null;
    }

    /** @return array{0: ?string, 1: ?string} */
    public static function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/u', trim((string) $name), 2, PREG_SPLIT_NO_EMPTY);

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    // ---------------------------------------------------------------- page config

    /** Everything toysland-tracker needs, rendered once per page as JSON. */
    public static function scriptConfig(): array
    {
        $user = auth()->user();
        $pixels = static::browserPixels();
        $via = Setting::get('pixels_via', 'direct');

        return [
            'currency' => static::currency(),
            'country' => Shop::country()->code,
            'locale' => app()->getLocale(),
            'page' => request()->route()?->getName(),
            'collect' => route('tracking.collect'),
            'progress' => route('tracking.checkout-progress'),
            'consent' => [
                'banner' => Setting::get('consent_banner', '1') === '1',
                'default' => Setting::get('consent_default', 'granted'),
                'cookie' => self::CONSENT_COOKIE,
            ],
            // With a GTM container in "gtm" mode, GTM owns the pixels; otherwise they load directly.
            'pixels' => $via === 'gtm' && isset($pixels['gtm']) ? ['gtm' => $pixels['gtm']] : $pixels,
            'user' => array_filter([
                'sha256_email_address' => $user && ! $user->is_admin ? static::hash(static::normalizeEmail($user->email)) : null,
                'sha256_phone_number' => $user && ! $user->is_admin ? static::hash(static::normalizePhone($user->phone, Shop::country()->code)) : null,
                'external_id' => static::hash(static::visitorId()),
            ]),
            'events' => static::pageEvents(),
        ];
    }
}
