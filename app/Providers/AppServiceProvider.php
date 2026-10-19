<?php

namespace App\Providers;

use App\Models\Category;
use App\Support\Cart;
use App\Support\Favorites;
use App\Support\Shop;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination::tailwind');

        View::composer('layouts.store', function ($view) {
            $view->with([
                'countries' => Shop::countries(),
                'navCategories' => once(fn () => Category::active()->get()),
                'cartCount' => Cart::count(),
                'favCount' => count(Favorites::ids()),
            ]);
        });

        // Child views of the store layout need the categories too (home, listing).
        View::composer(['store.home', 'store.products', 'store.category'], function ($view) {
            $view->with('navCategories', once(fn () => Category::active()->get()));
        });

        // Header/footer partials and invoice share the brand name and direction.
        View::composer(['partials.header', 'partials.footer', 'store.invoice'], function ($view) {
            $view->with([
                'brand' => \App\Models\Setting::tr('store_name', __('store.brandName')),
                'rtl' => Shop::isRtl(),
            ]);
        });
    }
}
