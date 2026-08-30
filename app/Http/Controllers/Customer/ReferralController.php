<?php

namespace App\Http\Controllers\Customer;

use App\Models\CustomerReferral;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ReferralController extends CustomerBaseController
{
    public function index(): View
    {
        $customerId = $this->customerId();
        $user = Auth::user();

        // Load all referrals where this customer is the referrer
        $referrals = CustomerReferral::with(['customer.customerDetail', 'booking.emiPlan', 'booking.product'])
            ->where('referrer_id', $customerId)
            ->latest('referred_at')
            ->get();

        // Calculate totals for summary cards
        $totalEarnedCashback = CustomerReferral::where('referrer_id', $customerId)->where('status', 'Completed')->sum('cashback_amount');
        $pendingCashback = CustomerReferral::where('referrer_id', $customerId)->whereIn('status', ['Pending', 'Under Review', 'Approved'])->sum('cashback_amount');

        return view('customer.referrals.index', compact('referrals', 'user', 'totalEarnedCashback', 'pendingCashback'));
    }
}
