<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Models\Referral;
use App\Models\GoldBooking;
use App\Models\BookingPayment;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        if (auth()->user()->isCustomer()) {
            return redirect()->route('customer.dashboard');
        }

        $stats = $this->reportService->getDashboardStats();

        // Custom stats for the dashboard template:
        $salesVal = $stats['monthly_collection']; // Monthly Collection
        $marginVal = $stats['outstanding_amount']; // Outstanding Amount
        $ordersVal = $stats['active_bookings']; // Active Bookings
        $affiliateVal = Referral::count(); // Total Referrals

        // Survey stats:
        $todayEarnings = $stats['today_collection']; // Today's Collection
        $productSold = $stats['gold_sold']; // Total Gold Weight Sold (grams)
        $todayOrders = GoldBooking::whereDate('booking_date', Carbon::today())->count(); // Today's Bookings

        // Total collected
        $totalCollected = BookingPayment::where('status', 'Paid')->sum('amount_paid');

        return view('dashboard.dashboard', compact(
            'salesVal',
            'marginVal',
            'ordersVal',
            'affiliateVal',
            'todayEarnings',
            'productSold',
            'todayOrders',
            'totalCollected'
        ));
    }
}
