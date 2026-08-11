<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandlePublicCors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Allowed local and configured origins
        $allowedOrigins = [
            'http://localhost',
            'http://127.0.0.1',
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost:8080',
            'http://127.0.0.1:8080',
            'http://localhost:3000',
            'http://127.0.0.1:3000',
        ];

        // Add configured production URL from environment if present
        $landingUrl = env('LANDING_WEBSITE_URL');
        if ($landingUrl) {
            $allowedOrigins[] = rtrim($landingUrl, '/');
        }

        $origin = $request->headers->get('Origin');
        $allowOrigin = null;

        if ($origin) {
            $normalizedOrigin = rtrim($origin, '/');
            if (in_array($normalizedOrigin, $allowedOrigins)) {
                $allowOrigin = $origin;
            }
        }

        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            if (!$allowOrigin) {
                return response('CORS Not Allowed', 403);
            }
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $allowOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization, Accept')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Add headers to response if it exists and supports headers
        if ($response && $allowOrigin && method_exists($response, 'header')) {
            $response->header('Access-Control-Allow-Origin', $allowOrigin)
                     ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                     ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization, Accept');
        }

        return $response;
    }
}
