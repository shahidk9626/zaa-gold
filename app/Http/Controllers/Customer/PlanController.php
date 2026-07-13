<?php

namespace App\Http\Controllers\Customer;

use Illuminate\View\View;

class PlanController extends CustomerBaseController
{
    public function index(): View
    {
        $plans = $this->customerService->getCustomerBookings($this->customerId());

        return view('customer.plans.index', compact('plans'));
    }

    public function show(int $id): View
    {
        $data = $this->customerService->getBookingDetails($id, $this->customerId());

        return view('customer.plans.show', $data);
    }
}
