<?php

namespace App\Services;

use App\Models\User;
use App\Models\Kyc;
use App\Models\CustomerDetail;
use App\Models\ActivityLog;
use App\Events\KycSubmittedEvent;
use App\Events\KycApprovedEvent;
use App\Events\KycRejectedEvent;
use App\Events\ResubmissionRequestedEvent;
use App\Events\ProfileReminderEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class CustomerOnboardingService
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Determine if customer profile is complete.
     */
    public function isProfileComplete(User $user): bool
    {
        return (bool) $user->profile_completed;
    }

    /**
     * Determine if customer KYC is approved.
     */
    public function isKycApproved(User $user): bool
    {
        return Kyc::where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Get visual status of KYC.
     */
    public function getKycStatus(User $user): string
    {
        $latestKyc = Kyc::where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (!$latestKyc) {
            return 'Draft';
        }

        return match ($latestKyc->status) {
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'resubmission_required' => 'Resubmission Required',
            default => ucfirst($latestKyc->status),
        };
    }

    /**
     * Check if Profile & KYC reminder popup should show.
     */
    public function shouldShowProfileReminder(User $user): bool
    {
        // 1. Must have at least one booking
        $bookingCount = $user->bookings()->count();
        if ($bookingCount === 0) {
            return false;
        }

        // 2. Profile must be Complete and KYC must be Approved
        if ($this->isProfileComplete($user) && $this->isKycApproved($user)) {
            return false;
        }

        // 3. Configurable interval check (session-based)
        $lastReminder = session('last_kyc_reminder_shown_at');
        $interval = config('kyc.reminder_interval', 900); // 15 mins default

        if ($lastReminder && (time() - $lastReminder) < $interval) {
            return false;
        }

        // Trigger Profile Reminder Event for future notification dispatchers
        event(new ProfileReminderEvent($user));

        return true;
    }

    /**
     * Determine if the user is eligible to request delivery.
     */
    public function canRequestDelivery(User $user): bool
    {
        return $this->isProfileComplete($user) && $this->isKycApproved($user);
    }

    /**
     * Complete the customer profile.
     */
    public function completeProfile(User $user, array $data): bool
    {
        $user->update($data['user'] ?? []);

        if ($user->customerDetail) {
            $user->customerDetail->update($data['detail'] ?? []);
        } else {
            $user->customerDetail()->create($data['detail'] ?? []);
        }

        $user->profile_completed = 1;
        $user->save();

        $this->logOnboardingActivity($user, 'profile_completed', 'Customer completed profile.');

        return true;
    }

    /**
     * Submit customer KYC documents.
     */
    public function submitKyc(User $user, array $files): Kyc
    {
        $documentNumber = $user->customerDetail->aadhar_number ?? 'N/A';
        $documentType = 'Aadhaar';

        $kycData = [
            'user_id' => $user->id,
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'status' => 'pending',
        ];

        // Store uploads
        if (isset($files['pan_card'])) {
            $kycData['pan_card'] = $files['pan_card']->store('kyc/pan_card', 'public');
        }
        if (isset($files['front_image'])) {
            $kycData['front_image'] = $files['front_image']->store('kyc/front', 'public');
        }
        if (isset($files['back_image'])) {
            $kycData['back_image'] = $files['back_image']->store('kyc/back', 'public');
        }
        if (isset($files['selfie'])) {
            $kycData['selfie'] = $files['selfie']->store('kyc/selfie', 'public');
        }
        if (isset($files['signature'])) {
            $kycData['signature'] = $files['signature']->store('kyc/signature', 'public');
        }
        if (isset($files['additional_documents'])) {
            $kycData['additional_documents'] = $files['additional_documents']->store('kyc/additional', 'public');
        }

        // Check if there is an existing non-approved KYC
        $latestKyc = Kyc::where('user_id', $user->id)
            ->where('status', '!=', 'approved')
            ->latest('id')
            ->first();

        // Check if this is a resubmission
        $isResubmission = false;
        if ($latestKyc) {
            if ($latestKyc->status === 'resubmission_required') {
                $isResubmission = true;
            }
            // Clean old files from storage before replacing them
            foreach (['pan_card', 'front_image', 'back_image', 'selfie', 'signature', 'additional_documents'] as $fileField) {
                if (isset($kycData[$fileField]) && $latestKyc->$fileField) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($latestKyc->$fileField);
                }
            }
            $latestKyc->update($kycData);
            $kyc = $latestKyc;
        } else {
            $kyc = Kyc::create($kycData);
        }

        // Update user status
        $user->status = 'active';
        $user->save();

        $action = $isResubmission ? 'kyc_resubmitted' : 'kyc_submitted';
        $description = $isResubmission ? 'Customer resubmitted KYC details.' : 'Customer submitted KYC details.';

        $this->logOnboardingActivity($user, $action, $description);

        event(new KycSubmittedEvent($kyc));

        return $kyc;
    }

    /**
     * Log onboarding activities to ActivityLog.
     */
    public function logOnboardingActivity(User $user, string $actionType, string $description): void
    {
        $userAgent = Request::header('User-Agent');
        $browser = $this->parseBrowser($userAgent);

        ActivityLog::create([
            'module_name' => 'customer_onboarding',
            'record_id' => $user->id,
            'action_type' => $actionType,
            'description' => $description,
            'created_by_id' => Auth::id() ?: $user->id,
            'ip_address' => Request::ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }

    protected function parseBrowser($userAgent)
    {
        if (empty($userAgent)) return 'Unknown';
        if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) return 'Internet Explorer';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) return 'Opera';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        return 'Unknown';
    }
}
