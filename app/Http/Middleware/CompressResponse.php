<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gzip HTML/XML/JSON in PHP so pages stay small even when the host does not
 * compress responses itself. Skipped when something already encoded the body.
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse
            || $response->headers->has('Content-Encoding')
            || ! str_contains((string) $request->header('Accept-Encoding'), 'gzip')
            || ! function_exists('gzencode')
            || ! preg_match('#text/html|xml|json|text/plain#', (string) $response->headers->get('Content-Type', 'text/html'))) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || strlen($content) < 1024) {
            return $response;
        }

        $response->setContent(gzencode($content, 6));
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding', false);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
