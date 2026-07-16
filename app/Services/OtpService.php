<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmailOtp;
use App\Mail\CustomerOtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OtpService
{
    /**
     * Generate and send OTP to the user.
     * Returns an array with the raw otp and a boolean indicating if mail sent successfully.
     */
    public function generateAndSendOtp(User $user, string $purpose): array
    {
        // 1. Invalidate any existing active OTPs of same purpose
        EmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        // 2. Generate 6-digit OTP
        $otp = (string) rand(100000, 999999);
        $hashedOtp = hash('sha256', $otp);

        // 3. Create EmailOtp record
        $emailOtp = EmailOtp::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'otp' => $hashedOtp,
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes(10),
            'attempts' => 0,
        ]);

        // 4. Log the action
        $this->logOtpActivity('otp_generated', $user, "OTP generated for {$purpose}");

        // 5. Send Email
        $mailSent = true;
        try {
            Mail::to($user->email)->send(new CustomerOtpMail($user, $otp, $purpose));
        } catch (\Exception $e) {
            Log::error("Failed to send OTP email to {$user->email} for purpose {$purpose}: " . $e->getMessage());
            $mailSent = false;
        }

        return [
            'otp' => $otp,
            'mail_sent' => $mailSent
        ];
    }

    /**
     * Validate an OTP.
     */
    public function validateOtp(User $user, string $otp, string $purpose): bool
    {
        $hashedOtp = hash('sha256', $otp);

        $emailOtp = EmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->first();

        if (!$emailOtp) {
            $this->logOtpActivity('otp_verification_failed', $user, "Failed OTP verification: No active OTP record found");
            return false;
        }

        // Check if expired
        if (Carbon::now()->timestamp > $emailOtp->expires_at->timestamp) {
            $emailOtp->delete();
            $this->logOtpActivity('otp_verification_failed', $user, "Failed OTP verification: OTP expired");
            return false;
        }

        // Check attempts throttle
        if ($emailOtp->attempts >= 5) {
            $emailOtp->delete();
            $this->logOtpActivity('otp_verification_failed', $user, "Failed OTP verification: Maximum attempts exceeded");
            return false;
        }

        // Increment attempts
        $emailOtp->increment('attempts');

        // Check if OTP matches
        if ($emailOtp->otp !== $hashedOtp) {
            if ($emailOtp->attempts >= 5) {
                $emailOtp->delete();
                $this->logOtpActivity('otp_verification_failed', $user, "Failed OTP verification: Maximum attempts exceeded (deleted)");
            } else {
                $this->logOtpActivity('otp_verification_failed', $user, "Failed OTP verification: Incorrect OTP entered (Attempt: {$emailOtp->attempts})");
            }
            return false;
        }

        // Success! Mark verified
        $emailOtp->update([
            'verified_at' => Carbon::now()
        ]);

        // Invalidate/delete OTP (Single use only, prevent replay)
        $emailOtp->delete();

        $this->logOtpActivity('otp_verified', $user, "OTP verified successfully for {$purpose}");

        return true;
    }

    /**
     * Resend OTP.
     * Returns an array with status indicating 'resent', 'throttled', or 'failed' and details.
     */
    public function resendOtp(User $user, string $purpose): array
    {
        // Check if there was an OTP sent within last 60 seconds
        $lastOtp = EmailOtp::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if ($lastOtp) {
            $secondsSinceLast = Carbon::now()->timestamp - $lastOtp->created_at->timestamp;
            if ($secondsSinceLast < 60) {
                return [
                    'status' => 'throttled',
                    'seconds_remaining' => 60 - $secondsSinceLast
                ];
            }
        }

        // Generate and send new OTP
        $result = $this->generateAndSendOtp($user, $purpose);

        $this->logOtpActivity('otp_resent', $user, "OTP resent to email for {$purpose}");

        return [
            'status' => 'resent',
            'mail_sent' => $result['mail_sent']
        ];
    }

    /**
     * Logs activity in the activity_logs table.
     */
    public function logOtpActivity(string $action, User $user, string $description)
    {
        $userAgent = request()->header('User-Agent');
        $browser = 'Unknown';
        if (!empty($userAgent)) {
            if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) $browser = 'Internet Explorer';
            elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
            elseif (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
            elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
            elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) $browser = 'Opera';
            elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
        }

        \App\Models\ActivityLog::create([
            'module_name' => 'customer_otp',
            'record_id' => $user->id,
            'action_type' => $action,
            'old_data' => null,
            'new_data' => null,
            'description' => $description,
            'created_by_id' => $user->id,
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
