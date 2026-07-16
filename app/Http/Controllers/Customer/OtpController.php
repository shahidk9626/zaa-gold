<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Show Verify Email OTP form.
     */
    public function verifyEmailView()
    {
        $email = session('verify_email');
        if (!$email) {
            return redirect()->route('login')->with('error', 'Session expired. Please log in.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        $otpRecord = \App\Models\EmailOtp::where('user_id', $user->id)
            ->where('purpose', 'registration')
            ->latest()
            ->first();

        $expiresAt = $otpRecord ? $otpRecord->expires_at : now()->addMinutes(10);
        $resendAfter = $otpRecord ? $otpRecord->created_at->addSeconds(60) : now();

        return view('customer.auth.verify-email', [
            'email' => $email,
            'user' => $user,
            'expiresAt' => $expiresAt->toIso8601String(),
            'resendAfter' => $resendAfter->toIso8601String(),
        ]);
    }

    /**
     * Process Verify Email OTP.
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $email = session('verify_email');
        if (!$email) {
            return redirect()->route('login')->with('error', 'Session expired. Please log in.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        if ($this->otpService->validateOtp($user, $request->otp, 'registration')) {
            // Activate account and mark email verified
            $user->status = 'active';
            $user->email_verified_at = now();
            $user->save();

            // Log the customer in
            Auth::login($user);

            // Send Welcome Email
            try {
                $user->load('customerDetail');
                Mail::to($user->email)->send(new \App\Mail\WelcomeCustomerMail($user));
            } catch (\Exception $mailEx) {
                Log::error("Customer Welcome Email Failed: " . $mailEx->getMessage());
            }

            session()->forget('verify_email');

            return redirect()->route('customer.dashboard')
                ->with('status', 'Email verified successfully! Welcome to your dashboard.');
        }

        return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.']);
    }

    /**
     * Resend Verify Email OTP.
     */
    public function resendEmailOtp()
    {
        $email = session('verify_email');
        if (!$email) {
            return redirect()->route('login')->with('error', 'Session expired. Please log in.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        $result = $this->otpService->resendOtp($user, 'registration');

        if ($result['status'] === 'throttled') {
            return back()->with('error', "Please wait {$result['seconds_remaining']} seconds before requesting a new OTP.");
        }

        if (!$result['mail_sent']) {
            return back()->with('warning', 'OTP was regenerated, but we could not send the email. Please try again later.');
        }

        return back()->with('status', 'A new verification OTP has been sent to your email.');
    }

    /**
     * Show Forgot Password OTP verification page.
     */
    public function verifyForgotPasswordView()
    {
        $email = session('reset_password_email');
        if (!$email) {
            return redirect()->route('password.request')->with('error', 'Please enter your email first.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'User not found.');
        }

        $otpRecord = \App\Models\EmailOtp::where('user_id', $user->id)
            ->where('purpose', 'forgot_password')
            ->latest()
            ->first();

        $expiresAt = $otpRecord ? $otpRecord->expires_at : now()->addMinutes(10);
        $resendAfter = $otpRecord ? $otpRecord->created_at->addSeconds(60) : now();

        return view('customer.auth.verify-forgot-password', [
            'email' => $email,
            'user' => $user,
            'expiresAt' => $expiresAt->toIso8601String(),
            'resendAfter' => $resendAfter->toIso8601String(),
        ]);
    }

    /**
     * Process Forgot Password OTP verification.
     */
    public function verifyForgotPassword(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $email = session('reset_password_email');
        if (!$email) {
            return redirect()->route('password.request')->with('error', 'Session expired. Please try again.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'User not found.');
        }

        if ($this->otpService->validateOtp($user, $request->otp, 'forgot_password')) {
            session(['reset_password_otp_verified' => true]);

            return redirect()->route('customer.reset-password-view');
        }

        return back()->withErrors(['otp' => 'Invalid or expired OTP. Please try again.']);
    }

    /**
     * Resend Forgot Password OTP.
     */
    public function resendForgotPasswordOtp()
    {
        $email = session('reset_password_email');
        if (!$email) {
            return redirect()->route('password.request')->with('error', 'Session expired.');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'User not found.');
        }

        $result = $this->otpService->resendOtp($user, 'forgot_password');

        if ($result['status'] === 'throttled') {
            return back()->with('error', "Please wait {$result['seconds_remaining']} seconds before requesting a new OTP.");
        }

        if (!$result['mail_sent']) {
            return back()->with('warning', 'OTP was regenerated, but we could not send the email. Please try again later.');
        }

        return back()->with('status', 'A new password reset OTP has been sent to your email.');
    }

    /**
     * Show password reset form.
     */
    public function resetPasswordView()
    {
        if (!session('reset_password_otp_verified') || !session('reset_password_email')) {
            return redirect()->route('password.request')->with('error', 'Unauthorized access. Please request an OTP first.');
        }

        return view('customer.auth.reset-password', ['email' => session('reset_password_email')]);
    }

    /**
     * Process password update.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (!session('reset_password_otp_verified') || !session('reset_password_email')) {
            return redirect()->route('password.request')->with('error', 'Unauthorized access.');
        }

        $user = User::where('email', session('reset_password_email'))->first();
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'User not found.');
        }

        // Update password
        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => \Illuminate\Support\Str::random(60),
        ])->save();

        // Log activity: Password Reset Completed
        $this->otpService->logOtpActivity('password_reset_completed', $user, "Customer password reset completed");

        // Clear session keys
        session()->forget(['reset_password_email', 'reset_password_otp_verified']);

        return redirect()->route('login')->with('status', 'Password updated successfully! Please log in with your new password.');
    }
}
