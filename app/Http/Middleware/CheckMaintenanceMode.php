<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MaintenanceModeService;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    protected MaintenanceModeService $maintenanceService;

    public function __construct(MaintenanceModeService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If maintenance mode is not enabled, let the request pass.
        if (!$this->maintenanceService->isEnabled()) {
            return $next($request);
        }

        // 2. Define the whitelist of routes or paths that are always allowed
        $path = $request->path();
        $normalizedPath = trim($path, '/');

        $allowedPaths = [
            'maintenance',
            'login',
            'logout',
            'api/public/system-status',
        ];

        if (in_array($normalizedPath, $allowedPaths, true)) {
            return $next($request);
        }

        // 3. Check if the user should bypass (e.g. authenticated Super Admin)
        if ($this->maintenanceService->shouldBypassMaintenance()) {
            return $next($request);
        }

        // 4. Return appropriate maintenance response
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'maintenance_mode' => true,
                'message' => 'AurOnGold is temporarily under maintenance.'
            ], 503, [
                'Retry-After' => '3600',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        }

        // Return the maintenance view directly with a 503 status code for web requests
        return response()->view('maintenance', [], 503)
            ->header('Retry-After', '3600')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
