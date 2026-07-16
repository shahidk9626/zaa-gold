<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $customerRole = \App\Models\Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : null;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $customerRoleId,
            'status' => 'inactive',
            'profile_completed' => 0,
        ]);

        if ($customerRoleId) {
            $slug = \Illuminate\Support\Str::slug($user->name . '-' . \Illuminate\Support\Str::random(5));
            \App\Models\CustomerDetail::create([
                'user_id' => $user->id,
                'slug' => $slug,
            ]);
        }

        event(new Registered($user));

        // Generate and send OTP via OtpService
        $otpService = app(\App\Services\OtpService::class);
        $otpService->logOtpActivity('register', $user, "Customer registered: {$user->name}");
        $result = $otpService->generateAndSendOtp($user, 'registration');

        session(['verify_email' => $user->email]);

        if (!$result['mail_sent']) {
            return redirect()->route('customer.verify-email-view')
                ->with('warning', 'Your account was created, but we could not send the verification email due to a mail server issue. Please request a resend.');
        }

        return redirect()->route('customer.verify-email-view')
            ->with('status', 'A verification OTP has been sent to your email.');
    }
}
