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
        $product = Product::where('slug', 'smart-talking-space-robot')->first();
        $addon = $product->options()->where('type', 'addon')->where('name_en', 'Luxury Gift Wrap')->first();
        $stock = $product->stock;

        $this->postJson('/cart', ['product_id' => $product->id, 'qty' => 2, 'addons' => [$addon->id]])
            ->assertOk()->assertJson(['count' => 2]);

        $this->postJson('/checkout/summary', ['coupon' => 'toys10', 'country' => 'KW'])
            ->assertOk()->assertJsonPath('coupon', 'TOYS10 (10%)');

        $this->post('/checkout', [
            'name' => 'Test Customer', 'phone' => '96599999999', 'country' => 'KW', 'city' => 'Salmiya',
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

        $this->get("/invoice/{$order->number}")->assertOk()->assertSee($order->number);
        $this->get('/cart')->assertSee(__('store.emptyCart'));

        // Another visitor cannot open the invoice, but can track with the matching phone.
        $this->flushSession();
        $this->get("/invoice/{$order->number}")->assertForbidden();
        $this->get("/tracking?order={$order->number}&phone=99999999")->assertOk()->assertSee($order->number);
        $this->get("/tracking?order={$order->number}&phone=11111111")->assertOk()->assertDontSee('#'.$order->number);
    }

    public function test_options_of_another_product_are_ignored(): void
    {
        [$a, $b] = Product::take(2)->get()->all();
        $foreign = $b->options()->where('type', 'addon')->first();

        $this->postJson('/cart', ['product_id' => $a->id, 'addons' => [$foreign->id]])->assertOk();
        $this->get('/cart')->assertSee(\App\Support\Shop::price($a->price))->assertDontSee($foreign->name_ar);
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
