<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /account',
            'Disallow: /invoice',
            'Disallow: /payment',
            'Disallow: /livewire',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** Every public page in both languages, linked with hreflang alternates. */
    public function sitemap()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $entries = collect([
                [route('home'), now(), '1.0'],
                [route('products.index'), now(), '0.9'],
                [route('faq'), null, '0.5'],
                [route('contact'), null, '0.5'],
            ]);
            foreach (Page::where('is_active', true)->get() as $page) {
                $entries->push([url('/'.$page->slug), $page->updated_at, '0.4']);
            }
            foreach (Category::where('is_active', true)->get() as $category) {
                $entries->push([route('category.show', $category), $category->updated_at, '0.8']);
            }
            foreach (Product::where('is_active', true)->get(['id', 'slug', 'image', 'updated_at']) as $product) {
                $entries->push([route('products.show', $product), $product->updated_at, '0.7', $product->image]);
            }

            return view('seo.sitemap', ['entries' => $entries])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public static function alternate(string $url, string $locale): string
    {
        return $locale === 'en' ? $url.'?lang=en' : $url;
    }
}
