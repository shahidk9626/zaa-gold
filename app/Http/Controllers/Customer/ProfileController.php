<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Services\CustomerOnboardingService;
use App\Services\CustomerService;
use App\Models\CustomerProfileUpdateRequest;

class ProfileController extends CustomerBaseController
{
    protected $onboardingService;

    public function __construct(CustomerOnboardingService $onboardingService, CustomerService $customerService)
    {
        parent::__construct($customerService);
        $this->onboardingService = $onboardingService;
    }

    public function index(): View
    {
        $user = Auth::user()->load(['customerDetail.documents', 'role']);
        $kycStatus = $this->onboardingService->getKycStatus($user);
        $isKycApproved = $this->onboardingService->isKycApproved($user);
        $latestKyc = \App\Models\Kyc::where('user_id', $user->id)->latest('id')->first();
        $hasPendingRequest = $user->hasPendingProfileUpdateRequest();
        $profileUpdateRequests = CustomerProfileUpdateRequest::where('customer_id', $user->id)
            ->latest('id')
            ->get();

        return view('customer.profile.index', compact(
            'user', 
            'kycStatus', 
            'isKycApproved', 
            'latestKyc', 
            'hasPendingRequest', 
            'profileUpdateRequests'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($this->onboardingService->isKycApproved($user)) {
            return back()->with('error', 'Your KYC is already verified/approved. Direct profile edits are disabled. Please use "Request Profile Update" to request changes.');
        }

        $request->validate([
            'phone' => 'required|string|max:15|unique:users,phone,' . $user->id,
            'whatsapp_number' => 'required|string|max:15',
            'father_name' => 'required|string|max:255',
            'nominee_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'occupation' => 'required|string|max:255',
            'pan_number' => 'required|string|max:20',
            'aadhar_number' => 'required|digits:12',
            'dob' => 'required|date',
            'gender' => 'required|string|max:20',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'ifsc_code' => 'required|string|max:255',
            'branch' => 'required|string|max:255',
            'emergency_contact' => 'required|string|max:15',
            'marital_status' => 'nullable|string|max:20',
            'alternate_number' => 'nullable|string|max:15',
        ]);

        $profileData = [
            'user' => $request->only(['phone', 'whatsapp_number']),
            'detail' => $request->only([
                'father_name', 'nominee_name', 'address', 'city', 'state', 'pincode', 'country',
                'occupation', 'pan_number', 'aadhar_number', 'dob', 'gender', 'bank_name',
                'account_number', 'ifsc_code', 'branch', 'emergency_contact', 'marital_status',
                'alternate_number'
            ])
        ];

        $this->onboardingService->completeProfile($user, $profileData);

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Submit a profile update request (For KYC Approved Customers)
     */
    public function requestUpdate(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$this->onboardingService->isKycApproved($user)) {
            return back()->with('error', 'Profile update request is only applicable to KYC approved customers.');
        }

        if ($user->hasPendingProfileUpdateRequest()) {
            return back()->with('error', 'You already have a profile update request pending with our team.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $fieldLabels = [
            'name' => 'Full Name',
            'email' => 'Email Address',
            'phone' => 'Mobile Number',
            'whatsapp_number' => 'WhatsApp Number',
            'father_name' => "Father's Name",
            'mother_name' => "Mother's Name",
            'nominee_name' => 'Nominee Name',
            'emergency_contact' => 'Emergency Contact',
            'alternate_number' => 'Alternate Number',
            'dob' => 'Date of Birth',
            'gender' => 'Gender',
            'marital_status' => 'Marital Status',
            'address' => 'Full Address',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'pincode' => 'Pincode',
            'occupation' => 'Occupation',
            'annual_income' => 'Annual Income',
            'pan_number' => 'PAN Card Number',
            'aadhar_number' => 'Aadhaar Card Number',
            'bank_name' => 'Bank Name',
            'account_number' => 'Account Number',
            'ifsc_code' => 'IFSC Code',
            'branch' => 'Branch Name',
        ];

        $userDetail = $user->customerDetail;
        $requestedChanges = [];

        foreach ($fieldLabels as $fieldKey => $label) {
            if ($request->has($fieldKey)) {
                $newValue = trim((string) $request->input($fieldKey));
                
                $oldValue = '';
                if (in_array($fieldKey, ['name', 'email', 'phone', 'whatsapp_number'])) {
                    $oldValue = (string) ($user->{$fieldKey} ?? '');
                } else {
                    $oldValue = (string) ($userDetail->{$fieldKey} ?? '');
                }

                if ($newValue !== '' && $newValue !== $oldValue) {
                    $requestedChanges[] = [
                        'field_name' => $fieldKey,
                        'field_label' => $label,
                        'current_value' => $oldValue ?: 'N/A',
                        'requested_value' => $newValue,
                    ];
                }
            }
        }

        if (empty($requestedChanges)) {
            return back()->with('error', 'Please change at least one field to submit a profile update request.');
        }

        CustomerProfileUpdateRequest::create([
            'customer_id' => $user->id,
            'requested_changes' => $requestedChanges,
            'reason' => trim($request->reason),
            'status' => 'Pending',
        ]);

        // Log Activity
        $this->onboardingService->logOnboardingActivity(
            $user, 
            'profile_update_requested', 
            "Customer submitted profile update request for review. Reason: {$request->reason}"
        );

        return back()->with('success', 'Your profile update request has been submitted successfully. Our team will review your request.');
    }

    public function submitKyc(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        // Profile must be complete before submitting KYC
        if (!$this->onboardingService->isProfileComplete($user)) {
            return back()->with('error', 'Please complete your Profile details before uploading KYC documents.');
        }

        $latestKyc = \App\Models\Kyc::where('user_id', $user->id)->latest('id')->first();
        $isUpdate = (bool)$latestKyc;

        // Dynamic validation: if a document is already uploaded, it is optional to re-upload.
        $request->validate([
            'pan_card' => ($isUpdate && $latestKyc->pan_card) ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'front_image' => ($isUpdate && $latestKyc->front_image) ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'back_image' => ($isUpdate && $latestKyc->back_image) ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'selfie' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'bank_document' => ($isUpdate && $latestKyc->bank_document) ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'additional_documents' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $this->onboardingService->submitKyc($user, $request->allFiles());

        return back()->with('success', 'KYC documents submitted successfully and are pending review.');
    }

    /**
     * Upload a new profile image.
     */
    public function uploadImage(Request $request): RedirectResponse
    {
        $request->validate([
            'profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = Auth::user();

        if ($request->hasFile('profile_image')) {
            // Delete old file if exists
            if ($user->profile_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_image);
            }

            // Save new file
            $path = $request->file('profile_image')->store('profile-images/customers', 'public');
            $user->profile_image = $path;
            $user->save();
        }

        return back()->with('success', 'Profile image updated successfully.');
    }

    /**
     * Remove the current profile image.
     */
    public function removeImage(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->profile_image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_image);
            $user->profile_image = null;
            $user->save();
        }

        return back()->with('success', 'Profile image removed successfully.');
    }
}
