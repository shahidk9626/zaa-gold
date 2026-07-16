<?php

namespace App\Http\Controllers\Customer;

use App\Models\GoldBooking;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Services\CustomerOnboardingService;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends CustomerBaseController
{
    protected $onboardingService;

    public function __construct(CustomerOnboardingService $onboardingService, CustomerService $customerService)
    {
        parent::__construct($customerService);
        $this->onboardingService = $onboardingService;
    }

    public function index(): View
    {
        $user = Auth::user();
        $deliveries = $this->customerService->getCustomerDeliveries($this->customerId());
        $canRequestDelivery = $this->onboardingService->canRequestDelivery($user);

        return view('customer.deliveries.index', compact('deliveries', 'canRequestDelivery'));
    }

    public function show(int $id): View
    {
        $user = Auth::user();
        $delivery = $this->customerService->getDeliveryDetails($id, $this->customerId());
        $canRequestDelivery = $this->onboardingService->canRequestDelivery($user);

        return view('customer.deliveries.show', compact('delivery', 'canRequestDelivery'));
    }

    public function storeRequest(int $bookingId, Request $request, DeliveryService $deliveryService): RedirectResponse
    {
        if (!$this->onboardingService->canRequestDelivery(Auth::user())) {
            return back()->with('error', 'Please complete your Profile and KYC verification before requesting Gold Delivery.');
        }

        $booking = GoldBooking::where('customer_id', $this->customerId())->findOrFail($bookingId);

        $rules = [
            'delivery_method' => 'required|in:Office Pickup,Courier,Branch Pickup',
            'remarks' => 'nullable|string|max:500',
        ];

        if ($request->delivery_method === 'Courier') {
            $rules['delivery_address'] = 'required|string|max:500';
        } elseif ($request->delivery_method === 'Branch Pickup') {
            $rules['pickup_branch'] = 'required|string|max:100';
            $rules['pickup_date'] = 'required|date|after_or_equal:today';
            $rules['pickup_time'] = 'required';
        }

        $request->validate($rules);

        try {
            $delivery = $deliveryService->requestDelivery($booking, $request->all());

            return redirect()->route('customer.deliveries.show', $delivery->id)
                ->with('success', "Delivery request {$delivery->delivery_number} submitted successfully.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
