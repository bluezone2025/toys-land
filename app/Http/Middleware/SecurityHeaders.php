<?php

namespace App\Http\Middleware;

use App\Support\StandardCookie;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers and cookie flags for every response.
 *
 * Store pages get a strict Content Security Policy: scripts and styles run only
 * from this site, the analytics/payment hosts, or tags carrying the per-request
 * nonce. The admin panel keeps a relaxed policy because its components evaluate
 * inline expressions.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);
        $secure = $request->isSecure();

        $html = str_contains((string) $response->headers->get('Content-Type'), 'text/html');
        if ($html) {
            $response->headers->set('Content-Security-Policy', implode('; ', $this->policy($request, $response, $nonce, $secure)));
        }
        if ($secure) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
        $response->headers->remove('X-Powered-By');
        header_remove('X-Powered-By');

        foreach ($response->headers->getCookies() as $cookie) {
            $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            $response->headers->setCookie(StandardCookie::from($cookie, $secure));
        }

        return $response;
    }

    private function policy(Request $request, Response $response, string $nonce, bool $secure): array
    {
        if ($request->is('admin', 'admin/*', 'livewire*', 'filament/*')) {
            $policy = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:",
                "style-src 'self' 'unsafe-inline' https:",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data: https:",
                "connect-src 'self' https:",
                "media-src 'self' blob: https:",
            ];
        } else {
            $policy = [
                "default-src 'none'",
                // Tag Manager, GA4 / Google Ads, Meta, TikTok and Snapchat pixels (see App\Support\Tracking).
                "script-src 'self' 'nonce-{$nonce}' https://*.googletagmanager.com https://www.google-analytics.com https://googleads.g.doubleclick.net https://www.googleadservices.com https://connect.facebook.net https://analytics.tiktok.com https://sc-static.net https://cdnjs.cloudflare.com",
                // Laravel's built-in error pages carry an unsigned <style> block.
                $response->getStatusCode() >= 400 ? "style-src 'self' 'unsafe-inline'" : "style-src 'self' 'nonce-{$nonce}'",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data:",
                "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com https://*.doubleclick.net https://www.google.com https://www.googleadservices.com https://www.facebook.com https://connect.facebook.net https://*.tiktok.com https://*.tiktokw.us https://tr.snapchat.com https://tr-shadow.snapchat.com https://*.snapchat.com",
                "media-src 'self' blob: https:",
                "frame-src https://www.google.com https://maps.google.com https://www.youtube.com https://www.facebook.com https://www.googletagmanager.com https://td.doubleclick.net https://tr.snapchat.com",
                "manifest-src 'self'",
            ];
        }

        return array_merge($policy, [
            "frame-ancestors 'self'",
            "form-action 'self' https:",
            "base-uri 'self'",
            "object-src 'none'",
        ], $secure ? ['upgrade-insecure-requests'] : []);
    }
}
