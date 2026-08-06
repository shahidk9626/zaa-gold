<?php

namespace App\Http\Controllers\Customer;

use Illuminate\View\View;

use App\Services\CustomerOnboardingService;
use App\Services\CustomerService;
use App\Services\AddressService;
use Illuminate\Support\Facades\Auth;

class MyPlanController extends CustomerBaseController
{
    protected $onboardingService;
    protected $addressService;

    public function __construct(CustomerOnboardingService $onboardingService, CustomerService $customerService, AddressService $addressService)
    {
        parent::__construct($customerService);
        $this->onboardingService = $onboardingService;
        $this->addressService = $addressService;
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
        $data['customerAddresses'] = $this->addressService->listForCustomer($this->customerId());

        return view('customer.my-plans.show', $data);
    }

    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $booking = \App\Models\GoldBooking::where('customer_id', $this->customerId())
            ->where('status', 'Draft')
            ->findOrFail($id);

        $booking->delete();

        \App\Models\ActivityLog::create([
            'module_name' => 'gold_booking',
            'record_id' => $booking->id,
            'action_type' => 'booking_deleted',
            'description' => "Customer deleted Draft booking #{$booking->booking_number}.",
            'created_by_id' => $this->customerId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);

        return redirect()->route('customer.my-plans.index')->with('success', 'Draft booking deleted successfully.');
    }
}
