<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    private function buildCsp(): array
    {
        // Build dynamic WebSocket connect-src from env so it works both locally and in production.
        $reverbScheme = env('REVERB_SCHEME', 'http');
        $reverbHost   = env('REVERB_HOST', 'localhost');
        $reverbPort   = env('REVERB_PORT', 8081);
        $wsScheme     = ($reverbScheme === 'https') ? 'wss' : 'ws';
        $wsOrigin     = $wsScheme . '://' . $reverbHost . ':' . $reverbPort;

        // Also allow the plain-wss variant when on HTTPS (some reverse-proxy setups strip the port).
        $wsOrigins = [$wsOrigin];
        if ($wsScheme === 'wss') {
            $wsOrigins[] = 'wss://' . $reverbHost;
        }

        return [
            "default-src 'self'",
            // Alpine.js uses new AsyncFunction() (unsafe-eval) and inline handlers (unsafe-inline).
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "img-src 'self' data: blob: https://ui-avatars.com",
            "connect-src 'self' " . implode(' ', $wsOrigins),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ];
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip non-HTML responses (JSON, file downloads, etc.)
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html') && ! $response->isRedirection()) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN'); // frame-ancestors 'self' in CSP allows same-origin iframes
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=()');
        $response->headers->set('Content-Security-Policy', implode('; ', $this->buildCsp()));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
