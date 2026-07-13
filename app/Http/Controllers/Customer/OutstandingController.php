<?php

namespace App\Http\Controllers\Customer;

use Illuminate\View\View;

class OutstandingController extends CustomerBaseController
{
    public function index(): View
    {
        $plans = $this->customerService->getCustomerBookings($this->customerId(), ['Booked', 'Active']);
        $totalOutstanding = collect($plans)->sum('outstanding');

        return view('customer.outstanding.index', compact('plans', 'totalOutstanding'));
    }
}
