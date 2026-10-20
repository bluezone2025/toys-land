<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [\App\Http\Controllers\SeoController::class, 'robots']);
Route::get('/sitemap.xml', [\App\Http\Controllers\SeoController::class, 'sitemap'])->name('sitemap');

// Arabic lives at "/", English at "/en/..." — same routes, clean crawlable URLs.
$storeRoutes = function () {
    Route::get('/', [StoreController::class, 'home'])->name('home');
    Route::get('/products', [StoreController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [StoreController::class, 'show'])->name('products.show');
    Route::get('/category/{category}', [StoreController::class, 'category'])->name('category.show');
    Route::get('/category/{category}/age/{age}', [StoreController::class, 'category'])->name('category.age');
    Route::get('/age/{age}', [StoreController::class, 'index'])->name('products.age');

    Route::get('/cart', [CartController::class, 'show'])->name('cart');
    Route::post('/cart', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{key}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{key}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::post('/checkout/summary', [CheckoutController::class, 'summary'])->middleware('throttle:60,1')->name('checkout.summary');
    Route::get('/payment/callback', [CheckoutController::class, 'callback'])->name('payment.callback');

    Route::get('/invoice', [OrderController::class, 'latest'])->name('invoice.latest');
    Route::get('/invoice/{order}', [OrderController::class, 'invoice'])->name('invoice.show');
    Route::get('/tracking', [OrderController::class, 'tracking'])->middleware('throttle:30,1')->name('tracking');

    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::delete('/favorites', [FavoriteController::class, 'clear'])->name('favorites.clear');

    Route::get('/about', fn () => app(PageController::class)->show('about'))->name('pages.about');
    Route::get('/privacy', fn () => app(PageController::class)->show('privacy'))->name('pages.privacy');
    Route::get('/terms', fn () => app(PageController::class)->show('terms'))->name('pages.terms');
    Route::get('/shipping', fn () => app(PageController::class)->show('shipping'))->name('pages.shipping');
    Route::get('/faq', [PageController::class, 'faq'])->name('faq');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::post('/contact', [PageController::class, 'storeContact'])->middleware('throttle:5,1')->name('contact.store');

    Route::get('/lang/{locale}', [PageController::class, 'locale'])->name('locale.switch');
    Route::get('/unsubscribe', [PageController::class, 'unsubscribe'])->name('unsubscribe');
    Route::post('/country', [PageController::class, 'country'])->name('country.switch');

    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::get('/account/{tab}', [AccountController::class, 'index'])->whereIn('tab', ['orders', 'favorites', 'addresses', 'account'])->name('account.tab');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::put('/account', [AccountController::class, 'update'])->name('account.update');
        Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');
        Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
        Route::patch('/account/addresses/{address}', [AccountController::class, 'defaultAddress'])->name('addresses.default');
        Route::delete('/account/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
    });
};

Route::middleware('store')->group($storeRoutes);
Route::prefix('en')->name('en.')->middleware('store')->group($storeRoutes);
