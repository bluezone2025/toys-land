<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StoreSeeder::class);
    }

    public function test_product_page_has_meta_hreflang_and_product_schema(): void
    {
        $p = Product::first();
        $html = $this->get("/products/{$p->slug}")->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.url("/products/{$p->slug}").'">', $html);
        $this->assertStringContainsString('hreflang="en" href="'.url("/en/products/{$p->slug}").'"', $html);
        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('"priceCurrency":"KWD"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('<meta property="og:type" content="product">', $html);
        $this->assertStringContainsString('<title>'.e($p->name_ar), $html);

        // English version lives at /en/... and old ?lang=en links redirect there permanently.
        $this->flushSession();
        $en = $this->get("/en/products/{$p->slug}")->assertSee('<title>'.e($p->name_en), false)->assertSee('lang="en"', false);
        $en->assertSee('<link rel="canonical" href="'.url("/en/products/{$p->slug}").'">', false);
        $en->assertSee(asset('favicon.svg'), false); // assets are not prefixed with /en
        $this->get("/products/{$p->slug}?lang=en")->assertRedirect(url("/en/products/{$p->slug}"))->assertStatus(301);
    }

    public function test_private_pages_are_noindex_and_public_ones_indexable(): void
    {
        foreach (['/cart', '/login', '/register', '/tracking', '/products?search=robot'] as $url) {
            $this->get($url)->assertSee('content="noindex, follow"', false);
        }
        $this->get('/')->assertSee('index, follow', false)->assertSee('"@type":"WebSite"', false)->assertSee('"@type":"Store"', false);
        $this->get('/faq')->assertSee('"@type":"FAQPage"', false);
    }

    public function test_custom_seo_fields_override_defaults(): void
    {
        $p = Product::first();
        $p->update(['seo_title_ar' => 'عنوان مخصص', 'seo_description_ar' => 'وصف مخصص للمنتج']);
        $this->get("/products/{$p->slug}")->assertSee('<title>عنوان مخصص |', false)->assertSee('content="وصف مخصص للمنتج"', false);
    }

    public function test_sitemap_and_robots(): void
    {
        $p = Product::first();
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.url("/en/products/{$p->slug}").'</loc>', false)
            ->assertSee('hreflang="x-default"', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_language_switch_link_is_crawlable(): void
    {
        $this->get('/about')->assertSee('href="'.url('/en/about').'" hreflang="en"', false);
        $this->get('/en/about')->assertSee('href="'.url('/about').'" hreflang="ar"', false);
        // Links on the home page carry no query strings.
        $html = $this->get('/')->getContent();
        preg_match_all('/href="([^"]+)"/', $html, $m);
        $internal = array_filter($m[1], fn ($u) => str_starts_with($u, url('/')) && ! str_contains($u, '/build/'));
        $this->assertSame([], array_values(array_filter($internal, fn ($u) => str_contains($u, '?'))));
    }
}
