<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use App\Support\UserAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Logs one row per storefront page view for the admin analytics.
 */
class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $ua = (string) $request->userAgent();
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()
            || $response->getStatusCode() !== 200 || UserAgent::isBot($ua)
            || (auth()->user()?->is_admin)) {
            return $response;
        }

        try {
            $referrer = $request->headers->get('referer');
            $referrerHost = $referrer ? parse_url($referrer, PHP_URL_HOST) : null;

            Visit::create([
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'path' => '/'.ltrim(substr($request->path(), 0, 490), '/'),
                'route' => $request->route()?->getName(),
                'product_id' => $request->route('product')?->id,
                'referrer' => $referrerHost && $referrerHost !== $request->getHost() ? $referrerHost : null,
                'device' => UserAgent::device($ua),
                'browser' => UserAgent::browser($ua),
                'platform' => UserAgent::platform($ua),
                'country' => $request->header('CF-IPCountry'),
                'locale' => app()->getLocale(),
                'created_at' => now(),
            ]);

            if ($user = auth()->user()) {
                $user->forceFill(['last_seen_at' => now(), 'locale' => app()->getLocale()])->saveQuietly();
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }
}
