<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetStoreLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // ?lang= in the URL wins (crawlable language versions), then the visitor's choice.
        if (in_array($request->query('lang'), ['ar', 'en'], true)) {
            session(['locale' => $request->query('lang')]);
        }
        $locale = session('locale') ?? Setting::get('default_locale', 'ar');
        app()->setLocale(in_array($locale, ['ar', 'en']) ? $locale : 'ar');

        return $next($request);
    }
}
