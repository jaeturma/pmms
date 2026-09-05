<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventStalePublicResults
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        // Public summaries are computed from committed records on every request.
        $contentType = (string) $response->headers->get('Content-Type');
        if (str_contains($contentType, 'text/html') || str_contains($contentType, 'application/json')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return $response;
    }
}
