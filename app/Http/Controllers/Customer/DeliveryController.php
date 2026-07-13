<?php

namespace App\Http\Controllers\Customer;

use App\Models\GoldBooking;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends CustomerBaseController
{
    public function index(): View
    {
        $deliveries = $this->customerService->getCustomerDeliveries($this->customerId());

        return view('customer.deliveries.index', compact('deliveries'));
    }

    public function show(int $id): View
    {
        $delivery = $this->customerService->getDeliveryDetails($id, $this->customerId());

        return view('customer.deliveries.show', compact('delivery'));
    }

    public function storeRequest(int $bookingId, Request $request, DeliveryService $deliveryService): RedirectResponse
    {
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
