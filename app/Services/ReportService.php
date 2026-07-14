<?php

namespace App\Services;

use App\Models\User;
use App\Models\GoldBooking;
use App\Models\BookingPayment;
use App\Models\BookingEmiSchedule;
use App\Models\BookingDelivery;
use App\Models\Product;
use App\Models\Referral;
use App\Models\SellOldGoldEnquiry;
use App\Models\FranchiseEnquiry;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Fetch all dashboard stats/cards
     */
    public function getDashboardStats()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfYear = Carbon::now()->startOfYear();
        $last30Days = Carbon::now()->subDays(30);

        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;

        // Today's Collection
        $todayCollection = BookingPayment::where('status', 'Paid')
            ->whereDate('payment_date', $today)
            ->sum('amount_paid');

        // Monthly Collection
        $monthlyCollection = BookingPayment::where('status', 'Paid')
            ->whereBetween('payment_date', [$startOfMonth, Carbon::now()->endOfMonth()])
            ->sum('amount_paid');

        // Yearly Collection
        $yearlyCollection = BookingPayment::where('status', 'Paid')
            ->whereBetween('payment_date', [$startOfYear, Carbon::now()->endOfYear()])
            ->sum('amount_paid');

        // Outstanding Amount
        $totalBooked = GoldBooking::whereNotIn('status', ['Cancelled', 'Refunded'])->sum('grand_total');
        $totalPaid = BookingPayment::where('status', 'Paid')->sum('amount_paid');
        $outstandingAmount = max($totalBooked - $totalPaid, 0);

        // Active Bookings
        $activeBookings = GoldBooking::whereIn('status', ['Active', 'Booked', 'Pending First EMI', 'Pending'])->count();

        // Completed Bookings
        $completedBookings = GoldBooking::where('status', 'Completed')->count();

        // Pending Deliveries
        $pendingDeliveries = BookingDelivery::whereIn('delivery_status', ['Requested', 'Approved', 'Ready For Dispatch', 'Dispatched', 'Out For Delivery'])->count();

        // Gold Sold
        $goldSold = GoldBooking::whereNotIn('status', ['Cancelled', 'Refunded'])->sum('gold_weight');

        // Pending EMI
        $pendingEmi = BookingEmiSchedule::where('status', 'Pending')->count();

        // Overdue EMI
        $overdueEmi = BookingEmiSchedule::where(function ($q) use ($today) {
            $q->where('status', 'Overdue')
              ->orWhere(function ($sq) use ($today) {
                  $sq->where('status', 'Pending')
                     ->where('due_date', '<', $today);
              });
        })->count();

        // Active Customers
        $activeCustomers = User::where('role_id', $customerRoleId)->where('status', 'active')->count();

        // New Customers
        $newCustomers = User::where('role_id', $customerRoleId)->where('created_at', '>=', $last30Days)->count();

        return [
            'today_collection' => $todayCollection,
            'monthly_collection' => $monthlyCollection,
            'yearly_collection' => $yearlyCollection,
            'outstanding_amount' => $outstandingAmount,
            'active_bookings' => $activeBookings,
            'completed_bookings' => $completedBookings,
            'pending_deliveries' => $pendingDeliveries,
            'gold_sold' => $goldSold,
            'pending_emi' => $pendingEmi,
            'overdue_emi' => $overdueEmi,
            'active_customers' => $activeCustomers,
            'new_customers' => $newCustomers,
        ];
    }

    /**
     * Get chart data for the last 6 months
     */
    public function getChartData()
    {
        $months = [];
        $collections = [];
        $bookingsCount = [];
        $goldSales = [];
        $emiCollections = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M Y');
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $months[] = $monthName;

            // Monthly Collection
            $collections[] = (float) BookingPayment::where('status', 'Paid')
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount_paid');

            // Booking Trend
            $bookingsCount[] = GoldBooking::whereBetween('booking_date', [$start, $end])->count();

            // Gold Sales
            $goldSales[] = (float) GoldBooking::whereNotIn('status', ['Cancelled', 'Refunded'])
                ->whereBetween('booking_date', [$start, $end])
                ->sum('gold_weight');

            // EMI Collection
            $emiCollections[] = (float) BookingPayment::where('status', 'Paid')
                ->whereNotNull('emi_schedule_id')
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount_paid');
        }

        // Delivery Status distributions
        $deliveryStatuses = BookingDelivery::select('delivery_status', DB::raw('count(*) as total'))
            ->groupBy('delivery_status')
            ->pluck('total', 'delivery_status')
            ->toArray();

        return [
            'months' => $months,
            'collections' => $collections,
            'bookings_trend' => $bookingsCount,
            'gold_sales' => $goldSales,
            'emi_collection' => $emiCollections,
            'delivery_status' => $deliveryStatuses,
        ];
    }

    /**
     * Apply common filters (date range, customer, product, status, booking, payment mode) to queries
     */
    public function applyFilters($query, array $filters, $tablePrefix = '')
    {
        $dateColumn = !empty($tablePrefix) ? $tablePrefix . '.created_at' : 'created_at';
        if (isset($filters['start_date']) && isset($filters['end_date']) && !empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween($dateColumn, [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
        }

        if (!empty($filters['customer_id'])) {
            $custColumn = !empty($tablePrefix) ? $tablePrefix . '.customer_id' : 'customer_id';
            // In User model, it is ID
            if ($tablePrefix === 'users') {
                $query->where('id', $filters['customer_id']);
            } else {
                $query->where($custColumn, $filters['customer_id']);
            }
        }

        if (!empty($filters['product_id'])) {
            $prodColumn = !empty($tablePrefix) ? $tablePrefix . '.product_id' : 'product_id';
            $query->where($prodColumn, $filters['product_id']);
        }

        if (!empty($filters['status'])) {
            $statusColumn = !empty($tablePrefix) ? $tablePrefix . '.status' : 'status';
            if ($tablePrefix === 'booking_deliveries') {
                $query->where('delivery_status', $filters['status']);
            } else {
                $query->where($statusColumn, $filters['status']);
            }
        }

        if (!empty($filters['booking_id'])) {
            $bookingColumn = !empty($tablePrefix) ? $tablePrefix . '.booking_id' : 'booking_id';
            $query->where($bookingColumn, $filters['booking_id']);
        }

        if (!empty($filters['payment_mode'])) {
            $modeColumn = !empty($tablePrefix) ? $tablePrefix . '.payment_mode' : 'payment_mode';
            $query->where($modeColumn, $filters['payment_mode']);
        }

        return $query;
    }

    /**
     * Fetch reports data
     */
    public function getReportQuery(string $reportType, array $filters)
    {
        switch ($reportType) {
            case 'booking':
                $query = GoldBooking::with(['customer', 'product', 'emiPlan']);
                $this->applyFilters($query, $filters, 'gold_bookings');
                // Customize date range filter to booking_date
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('booking_date', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                return $query;

            case 'payment':
                $query = BookingPayment::with(['customer', 'booking.product']);
                $this->applyFilters($query, $filters, 'booking_payments');
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('payment_date', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                return $query;

            case 'customer':
                $customerRole = Role::where('slug', 'customer')->first();
                $customerRoleId = $customerRole ? $customerRole->id : 0;
                $query = User::with(['customerDetail'])->where('role_id', $customerRoleId);
                $this->applyFilters($query, $filters, 'users');
                return $query;

            case 'product':
                $query = Product::query();
                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                return $query;

            case 'delivery':
                $query = BookingDelivery::with(['customer', 'booking.product']);
                $this->applyFilters($query, $filters, 'booking_deliveries');
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('request_date', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                return $query;

            case 'emi':
                $query = BookingEmiSchedule::with(['booking.customer', 'booking.product']);
                $this->applyFilters($query, $filters, 'booking_emi_schedules');
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('due_date', [$filters['start_date'], $filters['end_date']]);
                }
                return $query;

            case 'outstanding':
                // Bookings that are Active or Booked with their paid calculation
                $query = GoldBooking::with(['customer', 'product'])
                    ->whereNotIn('status', ['Cancelled', 'Refunded']);
                $this->applyFilters($query, $filters, 'gold_bookings');
                return $query;

            case 'referral':
                $query = Referral::with(['referrer', 'referred', 'booking']);
                if (!empty($filters['status'])) {
                    $query->where('reward_status', $filters['status']);
                }
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('created_at', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                return $query;

            case 'sell_old_gold':
                $query = SellOldGoldEnquiry::with('assignedStaff');
                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('created_at', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                return $query;

            case 'franchise':
                $query = FranchiseEnquiry::with('assignedStaff');
                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('created_at', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                return $query;

            default:
                throw new \Exception("Invalid report type");
        }
    }
}
