<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected AuditTrailService $auditTrailService)
    {
    }

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        if ($user && $user->isCustomer() && !$user->email_verified_at) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            session(['verify_email' => $user->email]);

            return redirect()->route('customer.verify-email-view')
                ->with('error', 'Please verify your email before logging in.');
        }

        $request->session()->regenerate();

        $this->auditTrailService->captureEvent(
            'authentication',
            $user?->id,
            'login',
            null,
            ['email' => $user?->email],
            'User logged in',
            $request,
            $user?->id,
            'user'
        );

        if ($user && $user->isCustomer()) {
            return redirect()->intended(route('customer.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->auditTrailService->captureEvent(
            'authentication',
            $user?->id,
            'logout',
            null,
            ['email' => $user?->email],
            'User logged out',
            $request,
            $user?->id,
            'user'
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
