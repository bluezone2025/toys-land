<?php

namespace App\Http\Middleware;

use App\Support\Tracking;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-party identity and attribution, set by the server so Safari's ITP does not cut them
 * to 24 hours / 7 days the way it does for cookies written by JavaScript:
 *  - tl_vid:  random visitor id (2 years) that ties page views, events and orders together;
 *  - tl_attr: first and last marketing touch (UTM tags, fbclid / ttclid / ScCid / gclid), 90 days;
 *  - _fbc:    Meta click id cookie, created from ?fbclid= when the pixel has not set it yet.
 */
class CaptureAttribution
{
    public const ATTRIBUTION_DAYS = 90;

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->hasSession() ? $request->session() : null;
        // The session copy keeps the journey together when a browser drops or blocks the cookie.
        $cookieId = Tracking::validUuid($request->cookie(Tracking::VISITOR_COOKIE));
        $visitorId = $cookieId ?? Tracking::validUuid($session?->get(Tracking::VISITOR_COOKIE)) ?? (string) Str::uuid();
        $request->attributes->set(Tracking::VISITOR_COOKIE, $visitorId);
        $session?->put(Tracking::VISITOR_COOKIE, $visitorId);

        $attribution = Tracking::attribution() ?: (array) $session?->get(Tracking::ATTRIBUTION_COOKIE, []);
        $touch = $request->isMethod('GET') && ! $request->ajax() ? Tracking::touchFrom($request) : null;
        if ($touch) {
            $attribution['first'] ??= $touch;
            $attribution['last'] = $touch;
            $request->attributes->set('tl_touch', $touch);
        }
        $request->attributes->set(Tracking::ATTRIBUTION_COOKIE, $attribution);
        if ($attribution) {
            $session?->put(Tracking::ATTRIBUTION_COOKIE, $attribution);
        }

        $response = $next($request);

        // Re-issue the visitor cookie once per session so it keeps rolling forward.
        if (! $cookieId || ($session && ! $session->get('tl_vid_refreshed'))) {
            Cookie::queue(Cookie::make(Tracking::VISITOR_COOKIE, $visitorId, 60 * 24 * 730, sameSite: 'lax'));
            $session?->put('tl_vid_refreshed', true);
        }
        if ($touch) {
            Cookie::queue(Cookie::make(Tracking::ATTRIBUTION_COOKIE, json_encode($attribution), 60 * 24 * self::ATTRIBUTION_DAYS, sameSite: 'lax'));
            if (isset($touch['fbclid']) && ! $request->cookie('_fbc')) {
                $domain = config('session.domain');
                Cookie::queue(Cookie::make('_fbc', 'fb.1.'.(int) (microtime(true) * 1000).'.'.$touch['fbclid'], 60 * 24 * self::ATTRIBUTION_DAYS, '/', $domain, null, false, false, 'lax'));
            }
        }

        return $response;
    }
}
