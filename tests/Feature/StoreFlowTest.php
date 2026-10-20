<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StoreSeeder::class);
    }

    public function test_storefront_pages_render(): void
    {
        $product = Product::first();
        foreach (['/', '/products', '/products?age=3-5', '/products?search=robot', '/category/dolls', "/products/{$product->slug}", '/cart', '/about', '/privacy', '/terms', '/shipping', '/faq', '/contact', '/login', '/register', '/tracking'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/lang/en');
        $this->get('/')->assertOk()->assertSee('Toys Land')->assertSee('dir="ltr"', false);
    }

    public function test_guest_can_buy_with_cash_on_delivery_and_coupon(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $product = Product::where('slug', 'smart-talking-space-robot')->first();
        $addon = $product->options()->where('type', 'addon')->where('name_en', 'Luxury Gift Wrap')->first();
        $stock = $product->stock;

        $this->postJson('/cart', ['product_id' => $product->id, 'qty' => 2, 'addons' => [$addon->id]])
            ->assertOk()->assertJson(['count' => 2]);

        $this->postJson('/checkout/summary', ['coupon' => 'toys10', 'country' => 'KW'])
            ->assertOk()->assertJsonPath('coupon', 'TOYS10 (10%)');

        $this->post('/checkout', [
            'name' => 'Test Customer', 'email' => 'buyer@example.com', 'phone' => '96599999999', 'country' => 'KW', 'city' => 'Salmiya',
            'block' => '4', 'property_type' => 'apartment', 'apartment' => '12', 'payment_method' => 'cod', 'coupon' => 'TOYS10',
        ])->assertRedirect();

        $order = Order::with('items')->firstOrFail();
        // (12.5 + 1.0) * 2 = 27.000, 10% off = 24.300, free shipping above 15 KD.
        $this->assertEquals(27.0, (float) $order->subtotal);
        $this->assertEquals(2.7, (float) $order->discount);
        $this->assertEquals(0.0, (float) $order->shipping);
        $this->assertEquals(24.3, (float) $order->total);
        $this->assertStringContainsString('Luxury Gift Wrap', $order->items->first()->options_en);
        $this->assertEquals($stock - 2, $product->fresh()->stock);

        // Thank-you email with invoice to the customer, alert to the admin, guest saved for marketing.
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\OrderPlaced::class, fn ($m) => $m->hasTo('buyer@example.com'));
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\NewOrderAdmin::class, fn ($m) => $m->hasTo('admin@toysland.com'));
        $this->assertDatabaseHas('guest_customers', ['email' => 'buyer@example.com', 'orders_count' => 1]);
        $html = (new \App\Mail\OrderPlaced($order))->render();
        $this->assertStringContainsString($order->number, $html);
        $this->assertStringContainsString('شكراً لطلبك', $html);
        $order->update(['locale' => 'en']);
        $this->assertStringContainsString('Thank you for your order', (new \App\Mail\OrderPlaced($order->fresh()))->render());
        $this->assertStringContainsString('طلب جديد', (new \App\Mail\NewOrderAdmin($order))->render());

        $this->get("/invoice/{$order->number}")->assertOk()->assertSee($order->number);
        $this->get('/cart')->assertSee(__('store.emptyCart'));

        // Another visitor cannot open the invoice, but can track with the matching phone.
        $this->flushSession();
        $this->get("/invoice/{$order->number}")->assertForbidden();
        $this->get("/tracking?order={$order->number}&phone=99999999")->assertOk()->assertSee($order->number);
        $this->get("/tracking?order={$order->number}&phone=11111111")->assertOk()->assertDontSee('#'.$order->number);
        // The signed link from the email opens the invoice without a session.
        $this->get(\Illuminate\Support\Facades\URL::signedRoute('invoice.show', $order))->assertOk()->assertSee($order->number);
    }

    public function test_options_of_another_product_are_ignored(): void
    {
        [$a, $b] = Product::take(2)->get()->all();
        $foreign = $b->options()->where('type', 'addon')->first();

        $this->postJson('/cart', ['product_id' => $a->id, 'addons' => [$foreign->id]])->assertOk();
        $this->get('/cart')->assertSee(\App\Support\Shop::price($a->price))->assertDontSee($foreign->name_ar);
    }

    public function test_cart_survives_a_new_session_and_shows_reminder(): void
    {
        $product = Product::first();
        $this->postJson('/cart', ['product_id' => $product->id])->assertOk();
        $token = \App\Models\SavedCart::firstOrFail()->token;

        // Returning visitor: new session, same browser cookie.
        $this->flushSession();
        $this->withCookie(\App\Support\Cart::COOKIE, $token)->get('/')
            ->assertSee('cart-reminder', false)->assertSee($product->image_url);
        $this->get('/cart')->assertSee($product->name_ar);
    }

    public function test_cart_reminder_email_is_sent_once(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user)->postJson('/cart', ['product_id' => Product::first()->id])->assertOk();
        \App\Models\SavedCart::query()->update(['updated_at' => now()->subHours(5)]);

        $this->artisan('cart:remind')->assertSuccessful();
        $this->artisan('cart:remind')->assertSuccessful();
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\CartReminder::class, 1);
    }

    public function test_validation_messages_follow_the_site_language(): void
    {
        \App\Models\User::factory()->create(['email' => 'taken@example.com']);
        $form = ['name' => 'A', 'email' => 'taken@example.com', 'password' => 'secret123', 'password_confirmation' => 'nope'];

        $this->from('/register')->post('/register', $form);
        $this->get('/register')->assertSee('مسجل عندنا من قبل')->assertSee('تأكيد كلمة المرور غير متطابق');

        $this->get('/lang/en');
        $this->from('/register')->post('/register', $form);
        $this->get('/register')->assertSee('The email has already been taken');
    }

    public function test_email_is_required_at_checkout(): void
    {
        $this->postJson('/cart', ['product_id' => Product::first()->id]);
        $this->post('/checkout', ['name' => 'X', 'phone' => '96512345678', 'country' => 'KW', 'city' => 'C', 'block' => '1', 'property_type' => 'house', 'apartment' => '1', 'payment_method' => 'cod'])
            ->assertSessionHasErrors('email');
    }

    public function test_cart_reminder_includes_promo_coupon_with_remaining_uses(): void
    {
        $coupon = \App\Models\Coupon::where('code', 'TOYS10')->first();
        $coupon->update(['max_uses' => 5, 'used_count' => 3]);
        \App\Models\Setting::put(['promo_coupon_id' => $coupon->id]);

        $user = \App\Models\User::factory()->create();
        $this->actingAs($user)->postJson('/cart', ['product_id' => Product::first()->id]);
        $html = (new \App\Mail\CartReminder(\App\Models\SavedCart::firstOrFail()))->render();
        $this->assertStringContainsString('TOYS10', $html);
        $this->assertStringContainsString('باقي 2 استخدام', $html);
        $this->get('/cart')->assertSee('TOYS10')->assertSee('باقي 2 استخدام');
    }

    public function test_campaign_queues_one_email_per_subscriber_and_unsubscribe_works(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $users = \App\Models\User::factory()->count(3)->create();
        $users[2]->update(['unsubscribed_at' => now()]);
        \App\Models\GuestCustomer::create(['email' => 'guest@example.com', 'name' => 'Guest', 'phone' => '1']);
        $campaign = \App\Models\EmailCampaign::create(['name' => 'C', 'subject_ar' => 'عرض', 'body_ar' => '<p>هلا {الاسم}</p>', 'repeat_hours' => 48, 'include_guests' => true]);

        $this->artisan('campaigns:run')->assertSuccessful();
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\CampaignMail::class, 3); // 2 users + 1 guest
        $this->assertSame(3, $campaign->fresh()->last_run_recipients);

        $this->artisan('campaigns:run')->assertSuccessful(); // not due again yet
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\CampaignMail::class, 3);

        $this->assertStringContainsString('هلا '.$users[0]->name, (new \App\Mail\CampaignMail($campaign, $users[0]->name, 'user', $users[0]->id))->render());
        $this->get(\Illuminate\Support\Facades\URL::signedRoute('unsubscribe', ['type' => 'user', 'id' => $users[0]->id]))->assertRedirect('/');
        $this->assertNotNull($users[0]->fresh()->unsubscribed_at);
    }

    public function test_shipping_fee_applies_below_threshold(): void
    {
        $product = Product::where('price', '<', 5)->first();
        $this->postJson('/cart', ['product_id' => $product->id])->assertOk();
        $this->postJson('/checkout/summary', ['country' => 'SA'])->assertOk()->assertJsonPath('shipping_free', false);
    }

    public function test_customer_account_favorites_and_admin_access(): void
    {
        $product = Product::first();
        $this->postJson('/favorites/toggle', ['product_id' => $product->id])->assertJson(['active' => true, 'count' => 1]);

        $this->post('/register', ['name' => 'Sara', 'email' => 'sara@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertRedirect('/account');
        $user = User::where('email', 'sara@example.com')->first();
        $this->assertTrue($user->favoriteProducts()->whereKey($product->id)->exists(), 'guest wishlist merged into account');

        foreach (['orders', 'favorites', 'addresses', 'account'] as $tab) {
            $this->get("/account?tab={$tab}")->assertOk();
        }
        $this->post('/account/addresses', ['title' => 'Home', 'area' => 'Hawally', 'details' => 'Block 1'])->assertRedirect();
        $this->assertTrue($user->addresses()->first()->is_default);

        $this->get('/admin')->assertForbidden();
        $this->post('/logout');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin')->assertOk();
    }
}
