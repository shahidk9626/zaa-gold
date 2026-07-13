<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectCustomersFromAdmin
{
    protected array $allowedRoutePrefixes = [
        'customer.',
        'login',
        'logout',
        'password.',
        'verification.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isCustomer()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && $this->isAllowedRoute($routeName)) {
            return $next($request);
        }

        if ($request->is('customer/*')) {
            return $next($request);
        }

        return redirect()->route('customer.dashboard');
    }

    protected function isAllowedRoute(string $routeName): bool
    {
        foreach ($this->allowedRoutePrefixes as $prefix) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
