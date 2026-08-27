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
            'phone' => ['required', 'string', 'max:15', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'referral_code' => ['nullable', 'string', 'max:50'],
        ]);

        $referredUser = null;
        $refCodeInput = $request->referral_code ? trim($request->referral_code) : null;

        if ($refCodeInput) {
            // Find referrer in users table
            $referredUser = \App\Models\User::where('referral_code', $refCodeInput)->first();
            
            // Fallback to staff emp_code
            if (!$referredUser) {
                $staffDetail = \App\Models\StaffDetail::where('emp_code', $refCodeInput)->first();
                if ($staffDetail && $staffDetail->user) {
                    $referredUser = $staffDetail->user;
                }
            }

            if (!$referredUser) {
                throw ValidationException::withMessages([
                    'referral_code' => 'Invalid referral code. Please check the code and try again.',
                ]);
            }

            if ($referredUser->status !== 'active') {
                throw ValidationException::withMessages([
                    'referral_code' => 'This referral code is no longer active.',
                ]);
            }

            // Prevent self-referral
            if ($referredUser->email === $request->email || $referredUser->phone === $request->phone) {
                throw ValidationException::withMessages([
                    'referral_code' => 'You cannot use your own referral code.',
                ]);
            }
        }

        $customerRole = \App\Models\Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : null;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $customerRoleId,
            'referred_by_staff_id' => $referredUser ? $referredUser->id : null,
            'status' => 'inactive',
            'profile_completed' => 0,
        ]);

        if ($referredUser) {
            $isStaff = $referredUser->isStaffOrAdmin();
            \App\Models\CustomerReferral::firstOrCreate(
                ['customer_id' => $user->id],
                [
                    'staff_id' => $isStaff ? $referredUser->id : null,
                    'referrer_id' => $referredUser->id,
                    'referrer_type' => $isStaff ? 'staff' : 'customer',
                    'referral_code' => $refCodeInput,
                    'referred_at' => now(),
                ]
            );
        }

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
