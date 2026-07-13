<?php

namespace App\Http\Controllers\Customer;

use Illuminate\View\View;

class NotificationController extends CustomerBaseController
{
    public function index(): View
    {
        $notifications = $this->customerService->getNotifications($this->customerId());

        return view('customer.notifications.index', compact('notifications'));
    }
}
