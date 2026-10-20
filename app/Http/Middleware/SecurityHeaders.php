<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    private const CSP = [
        "default-src 'self'",
        // Alpine.js uses new AsyncFunction() to evaluate x-data expressions (requires unsafe-eval).
        // It also uses inline event handlers for x-on:* directives (requires unsafe-inline).
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
        "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
        "img-src 'self' data: blob: https://ui-avatars.com",
        "connect-src 'self'",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        "upgrade-insecure-requests",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip non-HTML responses (JSON, file downloads, etc.)
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html') && ! $response->isRedirection()) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=()');
        $response->headers->set('Content-Security-Policy', implode('; ', self::CSP));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
