<?php

namespace App\Support;

use App\Models\AbandonedCheckout;
use App\Models\AnalyticsEvent;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Visit;
use Illuminate\Support\Carbon;

/**
 * Queries behind the admin tracking pages: funnel, attribution, product and list performance,
 * checkout recovery and ad-platform delivery health. Visitors are counted by the first-party
 * visitor id (falling back to the session for rows recorded before it existed).
 */
class TrackingReports
{
    public const FUNNEL = [
        'visitors' => 'زاروا المتجر',
        'view_item' => 'شافوا منتج',
        'add_to_cart' => 'أضافوا للسلة',
        'begin_checkout' => 'بدأوا الدفع',
        'add_shipping_info' => 'أدخلوا العنوان',
        'add_payment_info' => 'اختاروا الدفع',
        'purchase' => 'اشتروا',
    ];

    public const PERIODS = ['today' => 'اليوم', '7' => 'آخر 7 أيام', '30' => 'آخر 30 يوم', '90' => 'آخر 90 يوم'];

    /** @return array{0: Carbon, 1: Carbon} */
    public static function range(?string $period): array
    {
        return [$period === 'today' ? now()->startOfDay() : now()->subDays((int) ($period ?: 30))->startOfDay(), now()];
    }

    protected static function who(): string
    {
        return 'COUNT(DISTINCT COALESCE(visitor_id, session_id))';
    }

    /** @return array<string, int> step => unique visitors */
    public static function funnel(Carbon $from, Carbon $to): array
    {
        $events = AnalyticsEvent::whereBetween('created_at', [$from, $to])
            ->whereIn('type', array_keys(self::FUNNEL))
            ->selectRaw('type, '.static::who().' as c')->groupBy('type')->pluck('c', 'type');

        $out = ['visitors' => (int) Visit::whereBetween('created_at', [$from, $to])->selectRaw(static::who().' as c')->value('c')];
        foreach (array_slice(array_keys(self::FUNNEL), 1) as $step) {
            $out[$step] = (int) ($events[$step] ?? 0);
        }

        return $out;
    }

    /** Sales by last marketing touch (UTM / click id / referrer) captured on the order. */
    public static function attribution(Carbon $from, Carbon $to): array
    {
        $rows = [];
        $key = fn (?array $t) => $t ? [($t['source'] ?? '(غير معروف)'), ($t['medium'] ?? '—'), ($t['campaign'] ?? '—')] : ['مباشر / بدون مصدر', '—', '—'];

        Analytics::sales()->whereBetween('created_at', [$from, $to])->latest('id')->limit(10000)
            ->get(['id', 'total', 'utm_data'])
            ->each(function ($order) use (&$rows, $key) {
                $k = $key($order->utm_data['last'] ?? null);
                $id = implode('|', $k);
                $rows[$id] ??= ['source' => $k[0], 'medium' => $k[1], 'campaign' => $k[2], 'visitors' => 0, 'orders' => 0, 'revenue' => 0.0];
                $rows[$id]['orders']++;
                $rows[$id]['revenue'] += (float) $order->total;
            });

        Visit::whereBetween('created_at', [$from, $to])->whereNotNull('utm_source')
            ->selectRaw('utm_source, utm_medium, utm_campaign, '.static::who().' as c')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')->get()
            ->each(function ($v) use (&$rows) {
                $id = implode('|', [$v->utm_source, $v->utm_medium ?? '—', $v->utm_campaign ?? '—']);
                $rows[$id] ??= ['source' => $v->utm_source, 'medium' => $v->utm_medium ?? '—', 'campaign' => $v->utm_campaign ?? '—', 'visitors' => 0, 'orders' => 0, 'revenue' => 0.0];
                $rows[$id]['visitors'] += (int) $v->c;
            });

        return collect($rows)->map(fn ($r) => $r + [
            'conversion' => $r['visitors'] > 0 ? round($r['orders'] / $r['visitors'] * 100, 1) : null,
            'aov' => $r['orders'] > 0 ? round($r['revenue'] / $r['orders'], 3) : 0,
        ])->sortByDesc(fn ($r) => [$r['revenue'], $r['visitors']])->values()->all();
    }

    /** Per product: views → carts → units sold, with conversion rates. */
    public static function products(Carbon $from, Carbon $to, int $limit = 25): array
    {
        $events = AnalyticsEvent::whereBetween('created_at', [$from, $to])->whereNotNull('product_id')
            ->whereIn('type', ['view_item', 'add_to_cart', 'add_to_wishlist', 'select_item'])
            ->selectRaw('product_id, type, COUNT(*) as c')->groupBy('product_id', 'type')->get()
            ->groupBy('product_id');

        $sales = OrderItem::query()
            ->whereIn('order_id', Analytics::sales()->whereBetween('created_at', [$from, $to])->select('id'))
            ->selectRaw('product_id, SUM(qty) as units, SUM(total) as revenue, COUNT(DISTINCT order_id) as orders')
            ->groupBy('product_id')->get()->keyBy('product_id');

        $ids = $events->keys()->merge($sales->keys())->filter()->unique();
        $names = Product::whereIn('id', $ids)->get(['id', 'name_ar', 'slug', 'stock'])->keyBy('id');

        return $ids->map(function ($id) use ($events, $sales, $names) {
            $byType = ($events[$id] ?? collect())->pluck('c', 'type');
            $views = (int) ($byType['view_item'] ?? 0);
            $carts = (int) ($byType['add_to_cart'] ?? 0);
            $sale = $sales[$id] ?? null;

            return [
                'id' => $id,
                'name' => $names[$id]->name_ar ?? '#'.$id,
                'slug' => $names[$id]->slug ?? null,
                'stock' => $names[$id]->stock ?? null,
                'clicks' => (int) ($byType['select_item'] ?? 0),
                'views' => $views,
                'wishlist' => (int) ($byType['add_to_wishlist'] ?? 0),
                'carts' => $carts,
                'orders' => (int) ($sale->orders ?? 0),
                'units' => (int) ($sale->units ?? 0),
                'revenue' => round((float) ($sale->revenue ?? 0), 3),
                'cart_rate' => $views ? round($carts / $views * 100, 1) : null,
                'buy_rate' => $views ? round(($sale->orders ?? 0) / $views * 100, 1) : null,
            ];
        })->sortByDesc(fn ($r) => [$r['revenue'], $r['views']])->take($limit)->values()->all();
    }

    /** Product lists / sliders: how often they were seen and clicked. */
    public static function lists(Carbon $from, Carbon $to): array
    {
        $rows = [];
        AnalyticsEvent::whereBetween('created_at', [$from, $to])->whereIn('type', ['view_item_list', 'select_item'])
            ->latest('id')->limit(30000)->get(['type', 'meta'])
            ->each(function ($e) use (&$rows) {
                $name = $e->meta['item_list_name'] ?? '—';
                $rows[$name] ??= ['list' => $name, 'impressions' => 0, 'clicks' => 0];
                $rows[$name][$e->type === 'select_item' ? 'clicks' : 'impressions']++;
            });

        return collect($rows)->map(fn ($r) => $r + ['ctr' => $r['impressions'] ? round($r['clicks'] / $r['impressions'] * 100, 1) : null])
            ->sortByDesc('impressions')->values()->all();
    }

    /** @return array{open: int, open_value: float, contacted: int, recovered: int, recovered_value: float, converted: int} */
    public static function checkouts(Carbon $from, Carbon $to): array
    {
        $rows = AbandonedCheckout::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as c, SUM(subtotal) as v')->groupBy('status')->get()->keyBy('status');

        return [
            'open' => (int) ($rows['open']->c ?? 0),
            'open_value' => (float) ($rows['open']->v ?? 0),
            'contacted' => (int) ($rows['contacted']->c ?? 0),
            'recovered' => (int) ($rows['recovered']->c ?? 0),
            'recovered_value' => (float) ($rows['recovered']->v ?? 0),
            'converted' => (int) ($rows['converted']->c ?? 0),
        ];
    }

    /** @return array<string, array{ok: int, failed: int, pending: int}> per platform, last 24 hours */
    public static function platformHealth(): array
    {
        $out = [];
        foreach (array_keys(Tracking::serverPlatforms()) as $platform) {
            $out[$platform] = ['ok' => 0, 'failed' => 0, 'pending' => 0];
        }
        AnalyticsEvent::where('created_at', '>=', now()->subDay())->whereNotNull('dispatch_status')
            ->latest('id')->limit(20000)->get(['dispatch_status', 'delivery'])
            ->each(function ($e) use (&$out) {
                foreach ($out as $platform => $_) {
                    $status = $e->delivery[$platform] ?? null;
                    if ($status === 'ok') {
                        $out[$platform]['ok']++;
                    } elseif ($status !== null) {
                        $out[$platform]['failed']++;
                    } elseif ($e->dispatch_status === 'pending') {
                        $out[$platform]['pending']++;
                    }
                }
            });

        return $out;
    }

    public static function label(string $type): string
    {
        return (Tracking::ICONS[$type] ?? '•').' '.(Tracking::LABELS[$type] ?? $type);
    }

    public static function money(float|string|null $kwd): string
    {
        return number_format((float) $kwd, 3).' د.ك';
    }

    /** Distinct visitors with an event of $type in the window (live widgets). */
    public static function liveCount(string $type, int $minutes): int
    {
        return (int) AnalyticsEvent::where('type', $type)->where('created_at', '>=', now()->subMinutes($minutes))
            ->selectRaw(static::who().' as c')->value('c');
    }
}
