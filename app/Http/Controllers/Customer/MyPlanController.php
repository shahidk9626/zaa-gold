<?php

namespace App\Http\Controllers\Customer;

use Illuminate\View\View;

use App\Services\CustomerOnboardingService;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Auth;

class MyPlanController extends CustomerBaseController
{
    protected $onboardingService;

    public function __construct(CustomerOnboardingService $onboardingService, CustomerService $customerService)
    {
        parent::__construct($customerService);
        $this->onboardingService = $onboardingService;
    }

    public function index(): View
    {
        $plans = $this->customerService->getCustomerBookings($this->customerId());

        return view('customer.my-plans.index', compact('plans'));
    }

    public function show(int $id): View
    {
        $data = $this->customerService->getBookingDetails($id, $this->customerId());
        $data['canRequestDelivery'] = $this->onboardingService->canRequestDelivery(Auth::user());

        return view('customer.my-plans.show', $data);
    }
}
