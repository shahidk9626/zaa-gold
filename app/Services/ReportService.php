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
use App\Models\CancellationRequest;
use App\Models\GstInvoice;
use App\Services\EmiCalculationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    public function __construct(
        protected FinancialCalculationService $financialService,
        protected EmiCalculationService $emiService
    ) {
    }

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
        $outstandingAmount = GoldBooking::whereNotIn('status', ['Cancelled', 'Refund Initiated', 'Refunded'])
            ->get()
            ->sum(fn (GoldBooking $booking) => $this->financialService->outstanding($booking));

        // Active Bookings
        $activeBookings = GoldBooking::whereIn('status', ['Active', 'Booked', 'Pending First EMI', 'Pending'])->count();

        // Completed Bookings
        $completedBookings = GoldBooking::where('status', 'Completed')->count();

        // Pending Deliveries
        $pendingDeliveries = BookingDelivery::whereIn('delivery_status', ['Requested', 'Pending Admin Approval', 'Approved', 'Hold', 'Ready For Dispatch'])->count();
        $pickupDeliveries = BookingDelivery::where('delivery_method', 'Branch Pickup')->count();
        $courierDeliveries = BookingDelivery::where('delivery_method', 'Courier')->count();
        $deliveredDeliveries = BookingDelivery::whereIn('delivery_status', ['Delivered', 'Collected'])->count();
        $inTransitDeliveries = BookingDelivery::whereIn('delivery_status', ['Dispatched', 'In Transit', 'Out For Delivery'])->count();
        $completedDeliveryDurations = BookingDelivery::whereNotNull('request_date')
            ->whereNotNull('delivered_date')
            ->get(['request_date', 'delivered_date'])
            ->map(fn (BookingDelivery $delivery) => $delivery->request_date->diffInHours($delivery->delivered_date));
        $averageDeliveryHours = $completedDeliveryDurations->isNotEmpty() ? $completedDeliveryDurations->avg() : 0;

        // Gold Sold
        $goldSold = GoldBooking::whereNotIn('status', ['Cancelled', 'Refund Initiated', 'Refunded'])->sum('gold_weight');

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
            'outstanding_amount' => $this->financialService->roundMoney($outstandingAmount),
            'active_bookings' => $activeBookings,
            'completed_bookings' => $completedBookings,
            'pending_deliveries' => $pendingDeliveries,
            'pickup_deliveries' => $pickupDeliveries,
            'courier_deliveries' => $courierDeliveries,
            'delivered_deliveries' => $deliveredDeliveries,
            'in_transit_deliveries' => $inTransitDeliveries,
            'average_delivery_hours' => round((float)($averageDeliveryHours ?? 0), 1),
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
            $goldSales[] = (float) GoldBooking::whereNotIn('status', ['Cancelled', 'Refund Initiated', 'Refunded'])
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
     * Compute the 14 financial KPI aggregates for the selected date range.
     */
    public function getFinancialSummaryStats(array $filters)
    {
        $startDate = $filters['start_date'] ?? Carbon::now('Asia/Kolkata')->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? Carbon::now('Asia/Kolkata')->toDateString();

        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        // 1. Bookings in date range
        $bookings = GoldBooking::whereNotIn('status', ['Cancelled', 'Refund Initiated', 'Refunded'])
            ->whereBetween('booking_date', [$startDateTime, $endDateTime])
            ->get();

        // Total Gold Value
        $totalGoldValue = (float) $bookings->sum('locked_gold_value');

        // Total GST on Gold
        $totalGstOnGold = (float) $bookings->sum('gst_on_gold_amount');

        // Processing Fees
        $totalProcessingFees = (float) $bookings->sum(fn ($b) => $b->emiPlan ? $this->emiService->calculateProcessingFee($b->emiPlan, $b->locked_gold_value) : 0.00);

        // Platform Convenience Fees & Delivery Charges (hardcoded to 0.00 as per project spec)
        $totalPlatformConvenienceFees = 0.00;
        $totalDeliveryCharges = 0.00;

        // Total Service Charges (Processing + Platform + Delivery)
        $totalServiceCharges = $totalProcessingFees + $totalPlatformConvenienceFees + $totalDeliveryCharges;

        // Total Storage Charges
        $totalStorageCharges = (float) $bookings->sum('storage_charge_amount');

        // Total Insurance Charges
        $totalInsuranceCharges = 0.00;

        // Total Price Lock Charges
        $totalPriceLockCharges = (float) $bookings->sum('finance_charge_amount');

        // Total GST on Charges
        $totalGstOnCharges = (float) $bookings->sum('gst_on_charges_amount');

        // 2. Successful payments received in date range
        $totalPaymentsReceived = (float) BookingPayment::where('status', 'Paid')
            ->whereBetween('payment_date', [$startDateTime, $endDateTime])
            ->sum('amount_paid');

        // 3. Outstanding for bookings in date range
        $totalOutstanding = (float) $bookings->sum(fn ($b) => $this->financialService->outstanding($b));

        // 4. Refunds in date range
        // Filter by refund_date if present, else fallback to refund_completed_at / created_at
        $totalRefunds = (float) CancellationRequest::whereIn('status', ['Approved', 'Refund Initiated', 'Refund Completed'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('refund_date', [$startDate, $endDate])
                  ->orWhere(function ($sq) use ($startDate, $endDate) {
                      $sq->whereNull('refund_date')
                         ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                  });
            })
            ->sum('refund_amount');

        // Net Collection
        $netCollection = $this->financialService->roundMoney($totalPaymentsReceived - $totalRefunds);

        return [
            'total_gold_value' => $this->financialService->roundMoney($totalGoldValue),
            'total_gst_on_gold' => $this->financialService->roundMoney($totalGstOnGold),
            'total_service_charges' => $this->financialService->roundMoney($totalServiceCharges),
            'total_gst_on_charges' => $this->financialService->roundMoney($totalGstOnCharges),
            'total_storage_charges' => $this->financialService->roundMoney($totalStorageCharges),
            'total_insurance_charges' => $this->financialService->roundMoney($totalInsuranceCharges),
            'total_price_lock_charges' => $this->financialService->roundMoney($totalPriceLockCharges),
            'total_processing_fees' => $this->financialService->roundMoney($totalProcessingFees),
            'total_platform_convenience_fees' => $this->financialService->roundMoney($totalPlatformConvenienceFees),
            'total_delivery_charges' => $this->financialService->roundMoney($totalDeliveryCharges),
            'total_payments_received' => $this->financialService->roundMoney($totalPaymentsReceived),
            'total_outstanding' => $this->financialService->roundMoney($totalOutstanding),
            'total_refunds' => $this->financialService->roundMoney($totalRefunds),
            'net_collection' => $netCollection,
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
            case 'financial_summary':
                return GoldBooking::query();

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
                    ->whereNotIn('status', ['Cancelled', 'Refund Initiated', 'Refunded']);
                $this->applyFilters($query, $filters, 'gold_bookings');
                return $query;

            case 'cancellation':
                $query = CancellationRequest::with(['customer', 'booking.emiPlan']);
                if (!empty($filters['status'])) {
                    $query->where('status', $filters['status']);
                }
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('created_at', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
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

            case 'purchase_limit':
                $customerRole = Role::where('slug', 'customer')->first();
                $customerRoleId = $customerRole ? $customerRole->id : 0;
                
                $bookingService = app(\App\Services\BookingService::class);
                $selectedYear = isset($filters['financial_year']) ? (int) $filters['financial_year'] : null;
                $date = null;
                if ($selectedYear) {
                    $date = \Carbon\Carbon::create($selectedYear, 4, 1);
                }
                list($start, $end) = $bookingService->getFinancialYearDates($date);

                $query = User::where('role_id', $customerRoleId)
                    ->select('users.*')
                    ->selectSub(function ($q) use ($start, $end) {
                        $q->from('gold_bookings')
                            ->whereColumn('gold_bookings.customer_id', 'users.id')
                            ->whereIn('gold_bookings.status', ['Booked', 'Active', 'Completed'])
                            ->whereBetween('gold_bookings.booking_date', [$start, $end])
                            ->selectRaw('COALESCE(SUM(gold_bookings.gold_weight), 0)');
                    }, 'purchased_weight');

                if (!empty($filters['customer_id'])) {
                    $query->where('users.id', $filters['customer_id']);
                }
                return $query;

            case 'charge_breakdown':
                $query = GoldBooking::with(['customer', 'product', 'emiPlan']);
                $this->applyFilters($query, $filters, 'gold_bookings');
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('booking_date', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                return $query;

            case 'service_charge':
                $query = GoldBooking::with(['customer', 'product', 'emiPlan']);
                $this->applyFilters($query, $filters, 'gold_bookings');
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('booking_date', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                return $query;

            case 'gst_report':
                $query = GstInvoice::with(['booking.product', 'customer']);
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('invoice_date', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                if (!empty($filters['customer_id'])) {
                    $query->where('customer_id', $filters['customer_id']);
                }
                if (!empty($filters['booking_id'])) {
                    $query->where('booking_id', $filters['booking_id']);
                }
                return $query;

            case 'payment_collection':
                $query = BookingPayment::with(['booking.product', 'customer', 'emiSchedule'])
                    ->leftJoin('payment_transactions', function ($join) {
                        $join->on('booking_payments.transaction_reference', '=', 'payment_transactions.gateway_payment_id')
                             ->orOn('booking_payments.transaction_reference', '=', 'payment_transactions.gateway_order_id');
                    })
                    ->select('booking_payments.*', 'payment_transactions.gateway as gateway_name', 'payment_transactions.gateway_payment_id as gateway_txn_id', 'payment_transactions.payment_type as pt_type');

                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $query->whereBetween('booking_payments.payment_date', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
                }
                if (!empty($filters['customer_id'])) {
                    $query->where('booking_payments.customer_id', $filters['customer_id']);
                }
                if (!empty($filters['booking_id'])) {
                    $query->where('booking_payments.booking_id', $filters['booking_id']);
                }
                if (!empty($filters['payment_mode'])) {
                    $query->where('booking_payments.payment_mode', $filters['payment_mode']);
                }
                if (!empty($filters['status'])) {
                    $query->where('booking_payments.status', $filters['status']);
                }
                
                // Add filter for payment type if passed
                if (!empty($filters['payment_type'])) {
                    if ($filters['payment_type'] === 'Initial EMAP') {
                        $query->whereNotNull('booking_payments.emi_schedule_id')
                              ->whereHas('emiSchedule', function($q) {
                                  $q->where('installment_number', 1);
                              });
                    } elseif ($filters['payment_type'] === 'EMI') {
                        $query->whereNotNull('booking_payments.emi_schedule_id')
                              ->whereHas('emiSchedule', function($q) {
                                  $q->where('installment_number', '>', 1);
                              });
                    } elseif ($filters['payment_type'] === 'Product Purchase') {
                        $query->whereNull('booking_payments.emi_schedule_id');
                    }
                }
                return $query;

            default:
                throw new \Exception("Invalid report type: " . $reportType);
        }
    }
}
