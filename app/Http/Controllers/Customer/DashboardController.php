<?php

namespace App\Http\Controllers\Customer;

use Illuminate\View\View;

class DashboardController extends CustomerBaseController
{
    public function index(): View
    {
        $customerId = $this->customerId();

        $plans = $this->customerService->getCustomerBookings($customerId, ['Booked', 'Active']);
        $goldPrice = $this->customerService->getGoldPriceWithTrend();
        $recentActivity = $this->customerService->getRecentActivity($customerId);

        return view('customer.dashboard', compact('plans', 'goldPrice', 'recentActivity'));
    }
}
