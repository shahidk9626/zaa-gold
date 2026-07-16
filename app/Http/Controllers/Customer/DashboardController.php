<?php

namespace App\Http\Controllers\Customer;

use Illuminate\View\View;
use App\Services\CustomerOnboardingService;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends CustomerBaseController
{
    protected $onboardingService;

    public function __construct(CustomerOnboardingService $onboardingService, CustomerService $customerService)
    {
        parent::__construct($customerService);
        $this->onboardingService = $onboardingService;
    }

    public function index(): View
    {
        $customerId = $this->customerId();
        $user = Auth::user();

        $plans = $this->customerService->getCustomerBookings($customerId, ['Booked', 'Active', 'Completed']);
        $goldPrice = $this->customerService->getGoldPriceWithTrend();
        $recentActivity = $this->customerService->getRecentActivity($customerId);

        // Fetch onboarding and KYC status indicators
        $kycStatus = $this->onboardingService->getKycStatus($user);
        $isProfileComplete = $this->onboardingService->isProfileComplete($user);
        $showReminderModal = $this->onboardingService->shouldShowProfileReminder($user);

        if ($showReminderModal) {
            // Set timestamp in session so that it follows the configurable interval
            session(['last_kyc_reminder_shown_at' => time()]);
        }

        return view('customer.dashboard', compact('plans', 'goldPrice', 'recentActivity', 'kycStatus', 'isProfileComplete', 'showReminderModal'));
    }

    public function dismissReminder(Request $request): JsonResponse
    {
        session(['last_kyc_reminder_shown_at' => time()]);
        return response()->json(['success' => true]);
    }
}
