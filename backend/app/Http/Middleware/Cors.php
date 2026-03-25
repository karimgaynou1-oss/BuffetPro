<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = array_filter(array_map(
            'trim',
            explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000'))
        ));

        $origin = $request->header('Origin', '');
        $allowed = in_array($origin, $allowedOrigins, true) ? $origin : ($allowedOrigins[0] ?? '*');

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $allowed)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);
        $response->headers->set('Access-Control-Allow-Origin', $allowed);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Vary', 'Origin');

        return $response;
    }
}
