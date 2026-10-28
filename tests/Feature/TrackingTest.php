<?php

namespace Tests\Feature;

use App\Models\AbandonedCheckout;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\VisitorProfile;
use App\Services\TrackingService;
use App\Support\Tracking;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StoreSeeder::class);
        Mail::fake();
    }

    protected function connectPlatforms(): void
    {
        Setting::put([
            'facebook_pixel_id' => '111', 'meta_capi_token' => 'meta-token',
            'tiktok_pixel_id' => 'TT1', 'tiktok_access_token' => 'tt-token',
            'snap_pixel_id' => 'snap-1', 'snap_capi_token' => 'snap-token',
            'ga4_id' => 'G-TEST', 'ga4_api_secret' => 'ga-secret',
        ]);
    }

    protected function checkout(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/checkout', $overrides + [
            'name' => 'Sara Ahmad', 'email' => 'Sara@Example.com', 'phone' => '99887766', 'country' => 'KW', 'city' => 'Salmiya', 'street' => '10',
            'block' => '4', 'property_type' => 'house', 'apartment' => '7', 'payment_method' => 'cod',
        ]);
    }

    public function test_landing_with_utm_sets_first_party_cookies_and_profile(): void
    {
        $response = $this->get('/?utm_source=instagram&utm_medium=paid&utm_campaign=ramadan&fbclid=ABC123');

        $response->assertOk()->assertCookie(Tracking::VISITOR_COOKIE)->assertCookie(Tracking::ATTRIBUTION_COOKIE)
            ->assertPlainCookie('_fbc')
            ->assertSee('id="tl-config"', false)
            ->assertSee("gtag('consent', 'default'", false);

        $profile = VisitorProfile::firstOrFail();
        $this->assertSame('instagram', $profile->first_source);
        $this->assertSame('ramadan', $profile->first_campaign);
        $this->assertSame(1, $profile->sessions_count);
        $this->assertSame('instagram', \App\Models\Visit::latest('id')->value('utm_source'));
        $this->assertStringStartsWith('fb.1.', $response->getCookie('_fbc', false)->getValue());
    }

    public function test_full_journey_is_recorded_and_purchase_is_sent_once_per_platform(): void
    {
        $this->connectPlatforms();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1]),
            'business-api.tiktok.com/*' => Http::response(['code' => 0, 'message' => 'OK']),
            'tr.snapchat.com/*' => Http::response(['status' => 'VALID']),
            'www.google-analytics.com/*' => Http::response('', 204),
        ]);
        $product = Product::where('slug', 'smart-talking-space-robot')->firstOrFail();

        $this->get('/?utm_source=tiktok&utm_medium=paid&utm_campaign=launch&ttclid=TT-CLICK')->assertOk();
        $this->get("/products/{$product->slug}")->assertOk()->assertSee('"event":"view_item"', false);

        // The browser's event id is reused by the server, and handed back for the pixels.
        $eventId = (string) Str::uuid();
        $this->withHeader('X-Event-Id', $eventId)->postJson('/cart', ['product_id' => $product->id, 'qty' => 2])
            ->assertOk()->assertJsonPath('tracking.event', 'add_to_cart')->assertJsonPath('tracking.event_id', $eventId)
            ->assertJsonPath('tracking.ecommerce.items.0.item_id', (string) $product->id)
            ->assertJsonPath('tracking.ecommerce.items.0.quantity', 2);

        $this->get('/checkout')->assertOk()->assertSee('"event":"begin_checkout"', false)->assertSee('id="tl-checkout"', false);
        // Reloading the checkout does not count a second checkout for the same cart.
        $this->get('/checkout')->assertOk();
        $this->assertSame(1, AnalyticsEvent::where('type', 'begin_checkout')->count());

        $this->postJson('/api/tracking/collect', ['events' => [
            ['name' => 'add_payment_info', 'event_id' => (string) Str::uuid(), 'params' => ['payment_type' => 'COD', 'value' => 25, 'items' => [['item_id' => (string) $product->id, 'quantity' => 2]]], 'url' => url('/checkout')],
        ]])->assertOk();

        $this->checkout()->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertNotNull($order->event_id);
        $this->assertSame('tiktok', $order->utm_data['last']['source']);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($order->tracking_sent_at);

        // Invoice page pushes the browser purchase once, with the order's event_id.
        $this->get("/invoice/{$order->number}")->assertOk()->assertSee('"event_id":"'.$order->event_id.'"', false);
        $order->refresh();
        $this->assertNotNull($order->browser_tracked_at);
        $this->get("/invoice/{$order->number}")->assertOk()->assertDontSee('"event":"purchase"', false);

        $purchase = AnalyticsEvent::where('type', 'purchase')->sole();
        $this->assertSame($order->event_id, $purchase->event_id);
        $this->assertSame('pending', $purchase->dispatch_status);
        $this->assertSame(hash('sha256', 'sara@example.com'), $purchase->capi['user']['em']);
        $this->assertSame(hash('sha256', '96599887766'), $purchase->capi['user']['ph']);
        $this->assertSame('TT-CLICK', $purchase->capi['user']['ttclid']);

        $this->artisan('tracking:dispatch')->assertSuccessful();

        $purchase->refresh();
        $this->assertSame('sent', $purchase->dispatch_status);
        $this->assertSame(['meta' => 'ok', 'tiktok' => 'ok', 'snap' => 'ok', 'ga4' => 'ok'], $purchase->delivery);
        $this->assertNull($purchase->capi);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'graph.facebook.com') && collect($r['data'])->contains(fn ($e) => $e['event_name'] === 'Purchase' && $e['event_id'] === $order->event_id && $e['custom_data']['order_id'] === $order->number));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'tiktok.com') && collect($r['data'])->contains(fn ($e) => $e['event'] === 'CompletePayment' && $e['user']['ttclid'] === 'TT-CLICK'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'snapchat.com') && collect($r['data'])->contains(fn ($e) => $e['event_name'] === 'PURCHASE'));
        // One batched request per platform; GA4 only gets the purchase (the browser tag covers the rest).
        Http::assertSentCount(4);
        $ga4 = collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'google-analytics'));
        $this->assertCount(1, $ga4);
        $this->assertSame('purchase', $ga4->first()[0]['events'][0]['name']);

        $profile = VisitorProfile::where('visitor_id', $order->visitor_id)->firstOrFail();
        $this->assertSame(4, (int) $profile->stage);
        $this->assertSame(1, $profile->orders_count);
        $this->assertSame('Sara Ahmad', $profile->name);
        $this->assertSame('tiktok', $profile->first_source);
        $this->assertEqualsCanonicalizing(['page_view', 'view_item', 'add_to_cart', 'begin_checkout', 'add_payment_info', 'purchase'], $profile->timeline()->pluck('type')->unique()->values()->all());
    }

    public function test_failed_platform_is_retried_without_resending_to_the_others(): void
    {
        $this->connectPlatforms();
        Http::fakeSequence('graph.facebook.com/*')->push(['error' => ['message' => 'Invalid token']], 400)->push(['events_received' => 1]);
        Http::fake(['business-api.tiktok.com/*' => Http::response(['code' => 0]), 'tr.snapchat.com/*' => Http::response([]), '*' => Http::response('', 204)]);

        $product = Product::firstOrFail();
        $this->get("/products/{$product->slug}");
        $event = AnalyticsEvent::where('type', 'view_item')->sole();

        app(TrackingService::class)->dispatchPending();
        $event->refresh();
        $this->assertSame('pending', $event->dispatch_status);
        $this->assertStringStartsWith('error: Meta 400', $event->delivery['meta']);
        $this->assertSame('ok', $event->delivery['tiktok']);

        app(TrackingService::class)->dispatchPending();
        $event->refresh();
        $this->assertSame('sent', $event->dispatch_status);
        Http::assertSentCount(4); // meta twice, tiktok and snap once; GA4 does not take view_item
    }

    public function test_consent_denied_keeps_first_party_analytics_but_skips_ad_platforms(): void
    {
        $this->connectPlatforms();
        $product = Product::firstOrFail();

        $this->withUnencryptedCookie(Tracking::CONSENT_COOKIE, 'denied')->get("/products/{$product->slug}")->assertOk();

        $event = AnalyticsEvent::where('type', 'view_item')->sole();
        $this->assertNull($event->dispatch_status);
        $this->assertNull($event->capi);
    }

    public function test_abandoned_checkout_is_captured_restored_and_converted(): void
    {
        $product = Product::firstOrFail();
        $this->get('/?utm_source=snapchat&utm_medium=paid');
        $this->postJson('/cart', ['product_id' => $product->id]);

        // Too little to identify the shopper: nothing stored.
        $this->postJson('/api/tracking/checkout-progress', ['phone' => '99'])->assertJson(['ok' => false]);
        $this->postJson('/api/tracking/checkout-progress', ['name' => 'Noor', 'phone' => '6655 4433', 'country' => 'KW'])->assertJson(['ok' => true]);
        $this->postJson('/api/tracking/checkout-progress', ['name' => 'Noor', 'phone' => '66554433', 'country' => 'KW', 'city' => 'Jabriya', 'block' => '5', 'step' => 'address'])->assertJson(['ok' => true]);

        $checkout = AbandonedCheckout::sole();
        $this->assertSame('+965 66554433', $checkout->phone);
        $this->assertSame('address', $checkout->step);
        $this->assertSame('snapchat', $checkout->utm_data['last']['source']);
        $this->assertSame(1, $checkout->items_count);
        $this->assertSame(1, AnalyticsEvent::where('type', 'checkout_progress')->count());
        $this->assertStringContainsString('wa.me/96566554433', $checkout->whatsappUrl());

        // A new browser opens the WhatsApp link: cart and form come back.
        $this->flushSession();
        $this->app['cookie']->flushQueuedCookies();
        $this->get($checkout->restoreUrl())->assertRedirect(route('checkout'))->assertSessionHasInput('phone', '66554433')->assertSessionHasInput('block', '5');
        $this->assertSame(1, \App\Support\Cart::count());

        $checkout->update(['status' => 'contacted']);
        $this->checkout(['phone' => '66554433'])->assertRedirect();
        $checkout->refresh();
        $this->assertSame('recovered', $checkout->status);
        $this->assertNotNull($checkout->order_id);
    }

    public function test_myfatoorah_webhook_confirms_paid_order_once(): void
    {
        config(['services.myfatoorah.key' => 'mf-key']);
        Http::fake(['*/v2/ExecutePayment' => Http::response(['IsSuccess' => true, 'Data' => ['InvoiceId' => 555, 'PaymentURL' => 'https://pay.test/555']])]);
        $product = Product::firstOrFail();
        $this->postJson('/cart', ['product_id' => $product->id]);
        $this->checkout(['payment_method' => 'knet'])->assertRedirect('https://pay.test/555');
        $order = Order::firstOrFail();
        $this->assertNull($order->confirmed_at);

        Http::fake(['*/v2/GetPaymentStatus' => Http::response(['IsSuccess' => true, 'Data' => ['CustomerReference' => $order->number, 'InvoiceStatus' => 'Paid', 'InvoiceTransactions' => [['TransactionStatus' => 'Succss', 'PaymentId' => 'PAY-1']]]])]);

        // The shopper closed the browser: only the webhook arrives — twice (MyFatoorah retries).
        $payload = ['EventType' => 1, 'Event' => 'TransactionsStatusChanged', 'Data' => ['InvoiceId' => 555, 'TransactionStatus' => 'SUCCESS']];
        $this->postJson('/payment/webhook', $payload)->assertOk();
        $this->postJson('/payment/webhook', $payload)->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('PAY-1', $order->payment_id);
        $this->assertNotNull($order->confirmed_at);
        $this->assertSame(1, AnalyticsEvent::where('type', 'purchase')->count());
        Mail::assertQueued(\App\Mail\OrderPlaced::class, 1);

        // Then the redirect lands as well: still a single purchase and a single email.
        $this->get('/payment/callback?paymentId=PAY-1')->assertRedirect(route('invoice.show', $order));
        $this->assertSame(1, AnalyticsEvent::where('type', 'purchase')->count());
        Mail::assertQueued(\App\Mail\OrderPlaced::class, 1);
    }

    public function test_webhook_cannot_mark_an_unpaid_order_paid(): void
    {
        config(['services.myfatoorah.key' => 'mf-key']);
        Http::fake(['*/v2/ExecutePayment' => Http::response(['IsSuccess' => true, 'Data' => ['InvoiceId' => 9, 'PaymentURL' => 'https://pay.test/9']])]);
        $this->postJson('/cart', ['product_id' => Product::firstOrFail()->id]);
        $this->checkout(['payment_method' => 'knet']);
        $order = Order::firstOrFail();

        Http::fake(['*/v2/GetPaymentStatus' => Http::response(['IsSuccess' => true, 'Data' => ['CustomerReference' => $order->number, 'InvoiceStatus' => 'Pending', 'InvoiceTransactions' => []]])]);
        $this->postJson('/payment/webhook', ['Data' => ['InvoiceId' => 9, 'TransactionStatus' => 'SUCCESS']])->assertOk();

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(0, AnalyticsEvent::where('type', 'purchase')->count());
    }

    public function test_collector_validates_and_deduplicates_browser_events(): void
    {
        $id = (string) Str::uuid();
        $event = ['name' => 'view_item_list', 'event_id' => $id, 'params' => ['item_list_name' => 'الأكثر طلباً', 'items' => [['item_id' => '1', 'item_name' => 'X', 'evil' => 'drop me']]], 'url' => url('/')];

        $this->postJson('/api/tracking/collect', ['events' => [$event, $event]])->assertOk();
        $this->postJson('/api/tracking/collect', ['events' => [$event]])->assertOk();
        $this->postJson('/api/tracking/collect', ['events' => [['name' => 'purchase', 'event_id' => (string) Str::uuid()]]])->assertUnprocessable();

        $stored = AnalyticsEvent::where('type', 'view_item_list')->sole();
        $this->assertSame('browser', $stored->source);
        $this->assertSame('الأكثر طلباً', $stored->meta['item_list_name']);
        $this->assertArrayNotHasKey('evil', $stored->meta['items'][0]);
    }

    public function test_phone_and_email_normalisation(): void
    {
        $this->assertSame('96599887766', Tracking::normalizePhone('0099 887 766', 'KW'));
        $this->assertSame('96599887766', Tracking::normalizePhone('+965 99887766', 'KW'));
        $this->assertSame('96596512345', Tracking::normalizePhone('96512345', 'KW')); // 8-digit local number starting with 965
        $this->assertSame('966501234567', Tracking::normalizePhone('0501234567', 'SA'));
        $this->assertSame('966501234567', Tracking::normalizePhone('00966501234567', 'SA'));
        $this->assertSame('sara@example.com', Tracking::normalizeEmail('  Sara@Example.COM '));
        $this->assertNull(Tracking::normalizeEmail('not-an-email'));
        $this->assertSame('123.456', Tracking::gaClientId('GA1.1.123.456'));
    }

    public function test_admins_and_bots_are_not_tracked(): void
    {
        $product = Product::firstOrFail();
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get("/products/{$product->slug}");
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get("/products/{$product->slug}");

        $this->assertSame(0, AnalyticsEvent::count());
        $this->assertSame(0, VisitorProfile::count());
    }

    public function test_tracking_admin_pages_render(): void
    {
        $this->connectPlatforms();
        $product = Product::firstOrFail();
        $this->get('/?utm_source=google&utm_medium=cpc');
        $this->get("/products/{$product->slug}");
        $this->postJson('/cart', ['product_id' => $product->id]);
        $this->postJson('/api/tracking/checkout-progress', ['name' => 'Ali', 'phone' => '55443322', 'country' => 'KW']);
        $this->checkout();
        $this->postJson('/cart', ['product_id' => $product->id]);
        $this->postJson('/api/tracking/checkout-progress', ['name' => 'Ali', 'phone' => '55443322', 'country' => 'KW']);

        $order = Order::firstOrFail();
        $profile = VisitorProfile::firstOrFail();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach (['/admin/live', '/admin/funnel', '/admin/funnel?filters[period]=today', '/admin/visitors', "/admin/visitors/{$profile->id}", '/admin/abandoned-checkouts',
            '/admin/tracking-events', "/admin/orders/{$order->number}", '/admin/settings?tab=التتبع-والبيكسلات::data::tab'] as $url) {
            $res = $this->get($url);
            $this->assertSame(200, $res->status(), $url.' '.$res->exception?->getMessage());
        }

        $this->get("/admin/visitors/{$profile->id}")->assertSee('أضاف للسلة')->assertSee($product->name_ar);

        foreach ([
            \App\Filament\Widgets\Tracking\LiveStats::class, \App\Filament\Widgets\Tracking\LiveEventsFeed::class, \App\Filament\Widgets\Tracking\HotLeads::class,
            \App\Filament\Widgets\Tracking\FunnelStats::class, \App\Filament\Widgets\Tracking\FunnelChart::class, \App\Filament\Widgets\Tracking\AttributionTable::class,
            \App\Filament\Widgets\Tracking\ProductPerformanceTable::class, \App\Filament\Widgets\Tracking\ListPerformanceTable::class,
            \App\Filament\Widgets\Tracking\RecoveryStats::class, \App\Filament\Widgets\Tracking\PlatformHealth::class,
        ] as $widget) {
            Livewire::test($widget)->assertOk();
        }
        Livewire::test(\App\Filament\Widgets\Tracking\AttributionTable::class)->assertSee('google');
        Livewire::test(\App\Filament\Widgets\Tracking\LiveEventsFeed::class)->assertSee('أضاف للسلة');
    }

    public function test_tracking_calls_do_not_use_up_the_checkout_rate_limit(): void
    {
        $product = Product::firstOrFail();
        $this->postJson('/cart', ['product_id' => $product->id]);
        for ($i = 0; $i < 15; $i++) {
            $this->postJson('/api/tracking/collect', ['events' => [['name' => 'select_item', 'event_id' => (string) Str::uuid()]]])->assertOk();
            $this->postJson('/checkout/summary', ['country' => 'KW'])->assertOk();
        }

        $this->checkout()->assertRedirect();
        $this->assertSame(1, Order::count());
    }
}
