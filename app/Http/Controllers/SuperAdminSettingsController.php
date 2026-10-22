<?php

namespace App\Http\Controllers;

use App\Services\AuditTrailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Super Admin's own account credentials. Access is restricted by the
 * `super-admin` route middleware; every action only touches $request->user().
 */
class SuperAdminSettingsController extends Controller
{
    public function __construct(protected AuditTrailService $auditTrailService)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.super_admin_settings.index', [
            'user' => $request->user(),
        ]);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('updateEmail', [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['required', 'current_password'],
        ]);

        if ($validated['email'] === $user->email) {
            return redirect()->route('admin.super-admin-settings.index')
                ->with('info', 'The new email address is the same as the current one. No changes were made.');
        }

        $oldEmail = $user->email;
        $user->email = $validated['email'];
        // Same convention as ProfileController: a changed email is no longer verified.
        $user->email_verified_at = null;
        $user->save();

        $this->auditTrailService->captureEvent(
            'user',
            $user->id,
            'super_admin_email_changed',
            ['email' => $oldEmail],
            ['email' => $user->email],
            "Super Admin {$user->name} (User #{$user->id}) changed their login email address."
        );

        return redirect()->route('admin.super-admin-settings.index')
            ->with('success', 'Email address updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ]);

        $user->password = Hash::make($validated['password']);
        // Rotate the remember-me token so "remember me" cookies on other devices stop working.
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Keep the current session (regenerated) and sign out other database sessions of this account.
        $request->session()->regenerate();
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $this->auditTrailService->captureEvent(
            'user',
            $user->id,
            'super_admin_password_changed',
            null,
            null,
            "Super Admin {$user->name} (User #{$user->id}) changed their password. Other sessions were signed out."
        );

        return redirect()->route('admin.super-admin-settings.index')
            ->with('success', 'Password updated successfully. Other signed-in sessions have been logged out.');
    }
}
