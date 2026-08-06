<?php

namespace App\Http\Controllers\Customer;

use App\Models\GoldBooking;
use App\Services\DeliveryService;
use App\Services\AddressService;
use App\Services\TrackingService;
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
        $trackingService = app(TrackingService::class);

        return view('customer.deliveries.show', compact('delivery', 'canRequestDelivery', 'trackingService'));
    }

    public function storeRequest(int $bookingId, Request $request, DeliveryService $deliveryService): RedirectResponse
    {
        if (!$this->onboardingService->canRequestDelivery(Auth::user())) {
            return back()->with('error', 'Please complete your Profile and KYC verification before requesting Gold Delivery.');
        }

        $booking = GoldBooking::where('customer_id', $this->customerId())->findOrFail($bookingId);

        $rules = [
            'delivery_method' => 'required|in:Courier,Branch Pickup',
            'remarks' => 'nullable|string|max:500',
        ];

        if ($request->delivery_method === 'Courier') {
            $rules['customer_address_id'] = 'required|integer|exists:customer_addresses,id';
        } elseif ($request->delivery_method === 'Branch Pickup') {
            $rules['preferred_pickup_date'] = 'required|date|after_or_equal:today';
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

    public function storeAddress(Request $request, AddressService $addressService)
    {
        $data = $request->validate([
            'address_name' => 'required|string|max:100',
            'mobile' => 'required|string|max:20',
            'alternate_mobile' => 'nullable|string|max:20',
            'house_no' => 'required|string|max:120',
            'street' => 'nullable|string|max:120',
            'area' => 'nullable|string|max:120',
            'landmark' => 'nullable|string|max:120',
            'city' => 'required|string|max:80',
            'state' => 'required|string|max:80',
            'pin_code' => 'required|string|max:20',
            'country' => 'required|string|max:80',
            'address_type' => 'required|in:Home,Office,Other',
            'is_default' => 'nullable|boolean',
        ]);

        $address = $addressService->createForCustomer($this->customerId(), $data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'address' => [
                    'id' => $address->id,
                    'address_name' => $address->address_name,
                    'mobile' => $address->mobile,
                    'city' => $address->city,
                    'state' => $address->state,
                    'pin_code' => $address->pin_code,
                    'address_type' => $address->address_type,
                    'full_address' => $address->full_address,
                    'is_default' => $address->is_default,
                ],
            ]);
        }

        return back()->with('success', 'Address added successfully.');
    }
}
