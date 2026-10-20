<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StoreSeeder::class);
        $this->admin = User::factory()->create(['is_admin' => true]);

        // Generate traffic and an order so widgets have data to render.
        $this->get('/');
        $this->get('/products?search=robot');
        $product = Product::first();
        $this->postJson('/cart', ['product_id' => $product->id]);
        $this->post('/checkout', [
            'name' => 'Visitor', 'email' => 'visitor@example.com', 'phone' => '96512345678', 'country' => 'KW', 'city' => 'Hawally',
            'block' => '1', 'property_type' => 'house', 'apartment' => '5', 'payment_method' => 'cod',
        ]);
    }

    public function test_every_admin_page_renders(): void
    {
        $order = Order::firstOrFail();
        $this->actingAs($this->admin);

        $urls = ['/admin', '/admin/analytics', '/admin/settings', '/admin/orders', "/admin/orders/{$order->number}",
            '/admin/products', '/admin/products/create', '/admin/products/'.Product::first()->slug.'/edit',
            '/admin/categories', '/admin/categories/create', '/admin/categories/educational/edit', '/admin/age-groups',
            '/admin/customers', '/admin/customers/create', "/admin/customers/{$this->admin->id}/edit", '/admin/coupons', '/admin/countries',
            '/admin/hero-slides', '/admin/abandoned-carts', '/admin/guest-buyers', '/admin/email-campaigns', '/admin/email-campaigns/create', '/admin/faqs', '/admin/content-pages', '/admin/content-pages/1/edit', '/admin/contact-messages'];

        foreach ($urls as $url) {
            $this->assertSame(200, $this->get($url)->status(), $url);
        }
        $widgets = [
            \App\Filament\Widgets\StoreStatsOverview::class, \App\Filament\Widgets\SalesChart::class, \App\Filament\Widgets\OrdersStatusChart::class,
            \App\Filament\Widgets\VisitorsChart::class, \App\Filament\Widgets\LatestOrders::class, \App\Filament\Widgets\TopProducts::class,
            \App\Filament\Widgets\Analytics\TrafficStats::class, \App\Filament\Widgets\Analytics\ActiveVisitorsTable::class,
            \App\Filament\Widgets\Analytics\TopPagesTable::class, \App\Filament\Widgets\Analytics\TrafficSourcesTable::class,
            \App\Filament\Widgets\Analytics\SearchTermsTable::class, \App\Filament\Widgets\Analytics\DevicesChart::class,
            \App\Filament\Widgets\Analytics\BrowsersChart::class, \App\Filament\Widgets\Analytics\HourlyTrafficChart::class,
        ];
        foreach ($widgets as $widget) {
            Livewire::test($widget)->assertOk();
        }
        Livewire::test(\App\Filament\Widgets\StoreStatsOverview::class)->assertSee('مبيعات اليوم')->assertSee('13.500 د.ك');
        Livewire::test(\App\Filament\Widgets\Analytics\SearchTermsTable::class)->assertSee('robot');
        Livewire::test(\App\Filament\Widgets\Analytics\ActiveVisitorsTable::class)->assertSee('/products');
        $this->get("/invoice/{$order->number}")->assertOk();
    }

    public function test_create_redirects_to_working_edit_page(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Filament\Resources\Categories\Pages\CreateCategory::class)
            ->fillForm(['title_ar' => 'تجربة', 'title_en' => 'Test', 'slug' => 'test-cat', 'image' => \Illuminate\Http\UploadedFile::fake()->image('a.jpg')])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin/categories/test-cat/edit');
        $this->get('/admin/categories/test-cat/edit')->assertOk();
    }

    public function test_product_with_several_categories_ages_and_own_options(): void
    {
        $this->actingAs($this->admin);
        $cats = \App\Models\Category::take(2)->pluck('id')->all();
        $ages = \App\Models\AgeGroup::take(2)->pluck('id')->all();

        Livewire::test(\App\Filament\Resources\Products\Pages\CreateProduct::class)
            ->fillForm([
                'name_ar' => 'لعبة تجربة', 'name_en' => 'Test Toy', 'slug' => 'test-toy', 'price' => 10, 'stock' => 5,
                'categories' => $cats, 'ageGroups' => $ages,
                'image' => \Illuminate\Http\UploadedFile::fake()->image('t.jpg'),
                'options' => [
                    ['type' => 'edition', 'name_ar' => 'عادي', 'name_en' => 'Basic', 'price' => 0, 'is_active' => true],
                    ['type' => 'addon', 'name_ar' => 'شريطة', 'name_en' => 'Ribbon', 'price' => 0.5, 'is_active' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin/products/test-toy/edit');

        $product = Product::where('slug', 'test-toy')->firstOrFail();
        $this->assertEqualsCanonicalizing($cats, $product->categories->pluck('id')->all());
        $this->assertCount(2, $product->ageGroups);
        $this->assertSame(['Basic', 'Ribbon'], $product->options->pluck('name_en')->all());
        $this->get('/admin/products/test-toy/edit')->assertOk();

        // Shows in each chosen category and only offers its own options.
        foreach (\App\Models\Category::whereIn('id', $cats)->get() as $category) {
            $this->get("/category/{$category->slug}")->assertSee('لعبة تجربة');
        }
        $this->get('/products/test-toy')->assertSee('شريطة')->assertDontSee('تغليف هدية كشخة');
    }

    public function test_settings_page_saves(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Settings::class)
            ->set('data.store_name_ar', 'متجر تجربة')
            ->set('data.pay_cod_enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('متجر تجربة', Setting::get('store_name_ar'));
        $this->assertSame('0', Setting::get('pay_cod_enabled'));
        $this->get('/')->assertSee('متجر تجربة');
    }
}
