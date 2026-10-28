<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Support\Tracking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Sends the queued server-side copies of tracked events to the ad platforms in batches:
 * Meta Conversions API, TikTok Events API, Snapchat Conversions API (v3) and GA4
 * Measurement Protocol (purchase only — the browser tag covers the rest, and GA4
 * deduplicates purchases by transaction_id).
 *
 * Runs from the scheduler every minute (`tracking:dispatch`), so a page request never
 * waits on an ad platform. Each event keeps a per-platform result for the admin event log.
 */
class TrackingService
{
    public const MAX_ATTEMPTS = 3;

    /** @return array{events: int, sent: int, failed: int} */
    public function dispatchPending(int $limit = 500): array
    {
        // Platforms reject events older than 7 days.
        AnalyticsEvent::where('dispatch_status', 'pending')->where('created_at', '<', now()->subDays(6))
            ->update(['dispatch_status' => 'failed', 'capi' => null]);

        $events = AnalyticsEvent::where('dispatch_status', 'pending')->orderBy('id')->limit($limit)->get();

        return $this->send($events);
    }

    /** @param  Collection<int, AnalyticsEvent>  $events */
    public function send(Collection $events, ?array $platforms = null): array
    {
        $platforms ??= Tracking::serverPlatforms();
        $results = [];

        foreach ($platforms as $platform => $config) {
            $batch = $events->filter(fn (AnalyticsEvent $e) => $e->capi && ($e->delivery[$platform] ?? null) !== 'ok' && $this->supports($platform, $e->type));
            foreach ($batch->chunk($platform === 'ga4' ? 1 : 200) as $chunk) {
                foreach ($this->deliver($platform, $config, $chunk) as $id => $status) {
                    $results[$id][$platform] = $status;
                }
            }
        }

        $sent = $failed = 0;
        foreach ($events as $event) {
            $delivery = array_merge($event->delivery ?? [], $results[$event->id] ?? []);
            $hasError = collect($delivery)->contains(fn ($s) => $s !== 'ok');
            $attempts = $event->dispatch_attempts + 1;
            $final = ! $hasError || $attempts >= self::MAX_ATTEMPTS;
            $status = $hasError ? ($final ? 'failed' : 'pending') : ($delivery ? 'sent' : 'skipped');
            $status === 'sent' ? $sent++ : ($status === 'failed' ? $failed++ : null);

            $event->forceFill([
                'delivery' => $delivery ?: null,
                'dispatch_attempts' => $attempts,
                'dispatch_status' => $status,
                // The hashed payload is only kept while a retry may still need it.
                'capi' => $final ? null : $event->capi,
            ])->saveQuietly();
        }

        return ['events' => $events->count(), 'sent' => $sent, 'failed' => $failed];
    }

    public function supports(string $platform, string $event): bool
    {
        return $platform === 'ga4' ? $event === 'purchase' : isset(Tracking::PLATFORM_EVENTS[$event]);
    }

    /**
     * One request for the chunk; when it is rejected, retry event by event so a single bad
     * event does not fail the rest of the batch.
     *
     * @return array<int, string> event id => "ok" | "error: …"
     */
    protected function deliver(string $platform, array $config, Collection $chunk): array
    {
        try {
            $this->{$platform}($config, $chunk->pluck('capi')->all());

            return $chunk->mapWithKeys(fn ($e) => [$e->id => 'ok'])->all();
        } catch (Throwable $e) {
            if ($chunk->count() === 1) {
                return [$chunk->first()->id => 'error: '.mb_substr($e->getMessage(), 0, 180)];
            }
        }

        return $chunk->mapWithKeys(fn ($e) => $this->deliver($platform, $config, collect([$e])))->all();
    }

    // ------------------------------------------------------------------ platforms

    protected function meta(array $config, array $events): void
    {
        $data = array_map(function (array $e) {
            $u = $e['user'];

            return array_filter([
                'event_name' => Tracking::PLATFORM_EVENTS[$e['name']][0],
                'event_time' => $e['time'],
                'event_id' => $e['event_id'],
                'event_source_url' => $e['url'],
                'action_source' => 'website',
                'user_data' => array_filter([
                    'em' => isset($u['em']) ? [$u['em']] : null,
                    'ph' => isset($u['ph']) ? [$u['ph']] : null,
                    'fn' => $u['fn'] ?? null,
                    'ln' => $u['ln'] ?? null,
                    'ct' => $u['ct'] ?? null,
                    'country' => $u['country'] ?? null,
                    'external_id' => isset($u['external_id']) ? [$u['external_id']] : null,
                    'client_ip_address' => $u['ip'] ?? null,
                    'client_user_agent' => $u['ua'] ?? null,
                    'fbp' => $u['fbp'] ?? null,
                    'fbc' => $u['fbc'] ?? null,
                ]),
                'custom_data' => array_filter([
                    'currency' => $e['currency'],
                    'value' => $e['value'],
                    'content_type' => $e['items'] ? 'product' : null,
                    'content_ids' => array_column($e['items'], 'id') ?: null,
                    'content_name' => $e['items'][0]['name'] ?? null,
                    'contents' => array_map(fn ($i) => ['id' => $i['id'], 'quantity' => $i['quantity'], 'item_price' => $i['price']], $e['items']) ?: null,
                    'num_items' => $e['items'] ? array_sum(array_column($e['items'], 'quantity')) : null,
                    'order_id' => $e['order_id'],
                    'search_string' => $e['search'],
                ], fn ($v) => $v !== null),
            ]);
        }, $events);

        $response = Http::timeout(15)->acceptJson()
            ->post('https://graph.facebook.com/'.config('services.meta.graph_version', 'v24.0').'/'.$config['pixel_id'].'/events', array_filter([
                'access_token' => $config['token'],
                'data' => $data,
                'test_event_code' => $config['test_code'] ?? null,
            ]));

        if (! $response->successful()) {
            throw new RuntimeException('Meta '.$response->status().': '.($response->json('error.error_user_msg') ?? $response->json('error.message') ?? $response->body()));
        }
    }

    protected function tiktok(array $config, array $events): void
    {
        $data = array_map(function (array $e) {
            $u = $e['user'];

            return [
                'event' => Tracking::PLATFORM_EVENTS[$e['name']][1],
                'event_time' => $e['time'],
                'event_id' => $e['event_id'],
                'user' => array_filter([
                    'email' => $u['em'] ?? null,
                    'phone' => $u['ph'] ?? null,
                    'external_id' => $u['external_id'] ?? null,
                    'ip' => $u['ip'] ?? null,
                    'user_agent' => $u['ua'] ?? null,
                    'ttclid' => $u['ttclid'] ?? null,
                    'ttp' => $u['ttp'] ?? null,
                ]),
                'page' => ['url' => $e['url']],
                'properties' => array_filter([
                    'currency' => $e['currency'],
                    'value' => $e['value'],
                    'content_type' => $e['items'] ? 'product' : null,
                    'contents' => array_map(fn ($i) => array_filter(['content_id' => $i['id'], 'content_name' => $i['name'], 'content_category' => $i['category'], 'price' => $i['price'], 'quantity' => $i['quantity']], fn ($v) => $v !== null), $e['items']) ?: null,
                    'order_id' => $e['order_id'],
                    'query' => $e['search'],
                ], fn ($v) => $v !== null),
            ];
        }, $events);

        $response = Http::timeout(15)->acceptJson()->withHeaders(['Access-Token' => $config['token']])
            ->post('https://business-api.tiktok.com/open_api/v1.3/event/track/', array_filter([
                'event_source' => 'web',
                'event_source_id' => $config['pixel_id'],
                'test_event_code' => $config['test_code'] ?? null,
                'data' => $data,
            ]));

        if (! $response->successful() || (int) $response->json('code', -1) !== 0) {
            throw new RuntimeException('TikTok: '.($response->json('message') ?? $response->status()));
        }
    }

    protected function snap(array $config, array $events): void
    {
        $data = array_map(function (array $e) {
            $u = $e['user'];

            return [
                'event_name' => Tracking::PLATFORM_EVENTS[$e['name']][2],
                'action_source' => 'WEB',
                'event_source_url' => $e['url'],
                'event_time' => $e['time'],
                'event_id' => $e['event_id'],
                'user_data' => array_filter([
                    'em' => isset($u['em']) ? [$u['em']] : null,
                    'ph' => isset($u['ph']) ? [$u['ph']] : null,
                    'external_id' => isset($u['external_id']) ? [$u['external_id']] : null,
                    'client_ip_address' => $u['ip'] ?? null,
                    'client_user_agent' => $u['ua'] ?? null,
                    'sc_click_id' => $u['sccid'] ?? null,
                    'sc_cookie1' => $u['scid'] ?? null,
                ]),
                'custom_data' => array_filter([
                    'currency' => $e['currency'],
                    'value' => $e['value'],
                    'content_ids' => array_column($e['items'], 'id') ?: null,
                    'num_items' => $e['items'] ? array_sum(array_column($e['items'], 'quantity')) : null,
                    'order_id' => $e['order_id'],
                    'search_string' => $e['search'],
                ], fn ($v) => $v !== null),
            ];
        }, $events);

        $response = Http::timeout(15)->acceptJson()
            ->post('https://tr.snapchat.com/v3/'.$config['pixel_id'].'/events?access_token='.urlencode($config['token']), ['data' => $data]);

        if (! $response->successful()) {
            throw new RuntimeException('Snapchat '.$response->status().': '.($response->json('message') ?? $response->json('reason') ?? $response->body()));
        }
    }

    protected function ga4(array $config, array $events): void
    {
        foreach ($events as $e) {
            $u = $e['user'];
            $response = Http::timeout(10)
                ->post('https://www.google-analytics.com/mp/collect?measurement_id='.urlencode($config['measurement_id']).'&api_secret='.urlencode($config['secret']), array_filter([
                    // Same client id as the browser's _ga cookie, so the sale joins the visitor's session.
                    'client_id' => $u['ga'] ?? ($u['external_id'] ?? 'server').'.'.$e['time'],
                    'user_id' => isset($u['user_id']) ? (string) $u['user_id'] : null,
                    'timestamp_micros' => $e['time'] * 1_000_000,
                    'events' => [[
                        'name' => $e['name'],
                        'params' => array_filter([
                            'transaction_id' => $e['order_id'],
                            'value' => $e['value'],
                            'currency' => $e['currency'],
                            'shipping' => $e['shipping'],
                            'coupon' => $e['coupon'],
                            'engagement_time_msec' => 1,
                            'items' => array_map(fn ($i) => array_filter(['item_id' => $i['id'], 'item_name' => $i['name'], 'item_category' => $i['category'], 'item_variant' => $i['variant'], 'price' => $i['price'], 'quantity' => $i['quantity']], fn ($v) => $v !== null), $e['items']),
                        ], fn ($v) => $v !== null),
                    ]],
                ]));

            if (! $response->successful()) {
                throw new RuntimeException('GA4 '.$response->status());
            }
        }
    }
}
