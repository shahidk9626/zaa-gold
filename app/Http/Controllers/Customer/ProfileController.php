<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Services\CustomerOnboardingService;
use App\Services\CustomerService;

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
        $latestKyc = \App\Models\Kyc::where('user_id', $user->id)->latest('id')->first();

        return view('customer.profile.index', compact('user', 'kycStatus', 'latestKyc'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

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
            'aadhar_number' => 'required|string|max:20',
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
            'selfie' => ($isUpdate && $latestKyc->selfie) ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'signature' => ($isUpdate && $latestKyc->signature) ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'additional_documents' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $this->onboardingService->submitKyc($user, $request->allFiles());

        return back()->with('success', 'KYC documents submitted successfully and are pending review.');
    }
}
