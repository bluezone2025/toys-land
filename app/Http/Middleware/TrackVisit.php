<?php

namespace App\Http\Middleware;

use App\Models\Visit;
use App\Support\Tracking;
use App\Support\UserAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Logs one row per storefront page view for the admin analytics and the visitor's journey.
 */
class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $ua = (string) $request->userAgent();
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()
            || $response->getStatusCode() !== 200 || UserAgent::isBot($ua)
            || Tracking::shouldIgnore()) {
            return $response;
        }

        try {
            $referrer = $request->headers->get('referer');
            $referrerHost = $referrer ? parse_url($referrer, PHP_URL_HOST) : null;
            $touch = $request->attributes->get('tl_touch') ?? [];

            $visit = Visit::create([
                'session_id' => session()->getId(),
                'visitor_id' => Tracking::visitorId(),
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
                'utm_source' => isset($touch['source']) ? mb_substr($touch['source'], 0, 100) : null,
                'utm_medium' => isset($touch['medium']) ? mb_substr($touch['medium'], 0, 100) : null,
                'utm_campaign' => $touch['campaign'] ?? null,
                'created_at' => now(),
            ]);
            Tracking::touchProfile($visit->visitor_id, 'page_view', ['visit' => $visit]);

            if ($user = auth()->user()) {
                $user->forceFill(['last_seen_at' => now(), 'locale' => app()->getLocale()])->saveQuietly();
            }
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }
}
