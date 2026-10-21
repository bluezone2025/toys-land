<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers for every page of the store and the admin panel.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $policy = [
            "default-src 'self'",
            // Inline scripts power the store widgets, analytics tags and the admin panel.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:",
            "style-src 'self' 'unsafe-inline' https:",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https:",
            "media-src 'self' blob: https:",
            "connect-src 'self' https:",
            "frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube.com",
            "frame-ancestors 'self'",
            "form-action 'self' https:",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        if ($request->isSecure()) {
            $policy[] = 'upgrade-insecure-requests';
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $policy));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
        $response->headers->remove('X-Powered-By');
        header_remove('X-Powered-By');

        return $response;
    }
}
