<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * The URL decides the language: "/en/..." is English, everything else Arabic.
 * While serving English, generated links get the "/en" prefix automatically.
 */
class SetStoreLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // Old "?lang=" links (already shared / indexed) move permanently to the clean URL.
        if (in_array($request->query('lang'), ['ar', 'en'], true) && $request->isMethod('GET')) {
            return redirect()->to(Seo::url($request->query('lang')), 301);
        }

        $locale = $request->segment(1) === 'en' ? 'en' : 'ar';
        app()->setLocale($locale);
        session(['locale' => $locale]);

        // Set explicitly every request so a long-lived worker never leaks the prefix.
        URL::useAssetOrigin($locale === 'en' ? $request->root() : null);
        URL::forceRootUrl($locale === 'en' ? $request->root().'/en' : null);

        return $next($request);
    }
}
