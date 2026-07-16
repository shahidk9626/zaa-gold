<?php

namespace App\Http\Controllers;

use App\Models\GoldBooking;
use App\Models\Product;
use App\Models\EmiPlan;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\BookingService;
use App\Services\EmiCalculationService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoldBookingController extends Controller
{
    protected $bookingService;
    protected $emiService;
    protected $paymentService;

    public function __construct(BookingService $bookingService, EmiCalculationService $emiService, PaymentService $paymentService)
    {
        $this->bookingService = $bookingService;
        $this->emiService = $emiService;
        $this->paymentService = $paymentService;
    }

    /**
     * Show bookings list with filters, search, and pagination
     */
    public function index(Request $request)
    {
        $query = GoldBooking::with(['customer', 'product', 'emiPlan'])->latest();

        // 1. Filter by Search Query
        $search = $request->input('search_query') ?? $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function ($custQ) use ($search) {
                      $custQ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('product', function ($prodQ) use ($search) {
                      $prodQ->where('sku', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // 2. Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter by Product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // 4. Filter by Date Range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('booking_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if ($request->ajax()) {
            $bookings = $query->get()->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer_name' => $booking->customer->name ?? 'N/A',
                    'customer_email' => $booking->customer->email ?? 'N/A',
                    'product_name' => $booking->product->name ?? 'N/A',
                    'product_gold_type' => $booking->product->gold_type ?? 'N/A',
                    'gold_weight' => number_format($booking->gold_weight, 2) . 'g',
                    'locked_price_per_gram' => '₹' . number_format($booking->locked_price_per_gram, 2) . '/g',
                    'monthly_emi' => '₹' . number_format($booking->monthly_emi, 2),
                    'grand_total' => '₹' . number_format($booking->grand_total, 2),
                    'booking_date' => $booking->booking_date->format('d M Y'),
                    'status' => $booking->status,
                    'view_url' => route('bookings.show', $booking->id),
                ];
            });
            return response()->json(['data' => $bookings]);
        }

        $bookings = $query->paginate(15)->withQueryString();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.bookings.index', compact('bookings', 'products'));
    }

    /**
     * Store booking inside database transaction
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'emi_plan_id' => 'required|exists:emi_plans,id',
            'remarks' => 'nullable|string',
            'payment_method' => 'required|in:pay_now,generate_link',
        ]);

        try {
            // Validate Purchase Limit
            $product = Product::findOrFail($request->product_id);
            $weight = (float)$product->weight_in_grams;
            if (!$this->bookingService->canPurchaseGold($request->customer_id, $weight)) {
                $purchased = $this->bookingService->getPurchasedWeightForFinancialYear($request->customer_id);
                $limit = \App\Models\SystemSetting::get('customer_max_purchase_grams', 100.00);

                \App\Models\ActivityLog::create([
                    'module_name' => 'gold_booking',
                    'record_id' => $request->customer_id,
                    'action_type' => 'purchase_blocked_limit',
                    'description' => "Purchase blocked for customer #{$request->customer_id}. Attempted weight: {$weight}g. Already purchased: {$purchased}g. Limit: {$limit}g.",
                    'created_by_id' => auth()->id() ?? 1,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);

                return response()->json([
                    'error' => "Purchase limit exceeded. Allowed: {$limit}g. Already purchased: {$purchased}g."
                ], 422);
            }

            // Create Draft Booking
            $booking = $this->bookingService->createDraftBookingForPayment(
                $request->customer_id,
                $request->product_id,
                $request->emi_plan_id,
                $request->remarks
            );

            // Initiate payment
            $payment = $this->paymentService->initiateBookingGatewayPayment($booking, $request->payment_method === 'pay_now');

            if ($request->payment_method === 'pay_now') {
                return response()->json([
                    'success' => true,
                    'message' => 'Booking initialized as draft. Redirecting to checkout...',
                    'checkout_url' => route('admin.booking-payments.checkout', $payment['transaction']->id),
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment link generated successfully.',
                    'payment_url' => $payment['transaction']->payment_url,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Booking failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Admin/Staff Payment Checkout
     */
    public function checkout(\App\Models\PaymentTransaction $transaction, \App\Services\CashfreeService $cashfreeService)
    {
        abort_unless(auth()->user()->isStaffOrAdmin(), 403);
        abort_unless($transaction->payment_type === 'booking', 404);

        $session = $transaction->gateway_response ?? [];

        return view('admin.payments.cashfree-checkout', [
            'transaction' => $transaction,
            'booking' => $transaction->booking,
            'paymentSessionId' => $session['payment_session_id'] ?? null,
            'cashfreeMode' => $cashfreeService->checkoutMode(),
            'cashfreeSdkUrl' => config('services.cashfree.sdk_url'),
        ]);
    }

    /**
     * Admin/Staff Payment Callback
     */
    public function callback(
        Request $request,
        \App\Models\PaymentTransaction $transaction,
        \App\Services\PaymentGatewayService $paymentGatewayService,
        \App\Services\PaymentProcessingService $paymentProcessingService
    ) {
        abort_unless(auth()->user()->isStaffOrAdmin(), 403);
        abort_unless($transaction->payment_type === 'booking', 404);

        try {
            $verification = $paymentGatewayService->verifyGatewayResponse($transaction);
            $transaction = $paymentProcessingService->confirmBookingPayment($transaction, $verification);
        } catch (\Throwable $e) {
            return redirect()->route('bookings.index')
                ->with('error', 'Payment verification failed. Please contact support with transaction ' . $transaction->transaction_number . '.');
        }

        if ($transaction->isSuccessful()) {
            return redirect()->route('bookings.show', $transaction->booking_id)
                ->with('success', 'Payment verified successfully. Booking is confirmed.');
        }

        return redirect()->route('bookings.index')
            ->with('error', 'Payment was not successful. The booking has not been confirmed.');
    }

    /**
     * Show booking details panel
     */
    public function show($id)
    {
        $booking = GoldBooking::with(['customer', 'product', 'emiPlan', 'certificate', 'statusHistory.changedBy', 'paymentTransactions'])->findOrFail($id);
        
        // Fetch actual EMI Schedule from database
        $schedule = \App\Models\BookingEmiSchedule::where('booking_id', $booking->id)
            ->orderBy('installment_number')
            ->get();

        // Fetch related payments and receipts
        $payments = \App\Models\BookingPayment::where('booking_id', $booking->id)
            ->with('emiSchedule')
            ->latest('payment_date')
            ->get();

        $receipts = $payments->where('status', 'Paid');

        // Calculate financial summaries for Outstanding tab
        $totalBooked = (float)$booking->grand_total;
        $totalPaid = (float)$receipts->sum('amount_paid');
        $outstandingBalance = round($totalBooked - $totalPaid, 2);
        
        $principalPaid = (float)$receipts->sum('principal_paid');
        $interestPaid = (float)$receipts->sum('interest_paid');
        $lateFeePaid = (float)$receipts->sum('late_fee_paid');
        $gstPaid = (float)$receipts->sum('gst_paid');

        // Fetch related activity logs
        $activityLogs = ActivityLog::where('module_name', 'gold_booking')
            ->where('record_id', $booking->id)
            ->with('user')
            ->latest()
            ->get();

        // Fetch related delivery request
        $delivery = \App\Models\BookingDelivery::where('booking_id', $booking->id)
            ->latest()
            ->first();
        $paymentSummary = app(\App\Services\PaymentReportService::class)->bookingSummary($booking);

        return view('admin.bookings.show', compact(
            'booking', 
            'schedule', 
            'payments', 
            'receipts', 
            'activityLogs',
            'totalBooked',
            'totalPaid',
            'outstandingBalance',
            'principalPaid',
            'interestPaid',
            'lateFeePaid',
            'gstPaid',
            'delivery',
            'paymentSummary'
        ));
    }

    /**
     * Download Price Lock Certificate PDF
     */
    public function downloadCertificate($id)
    {
        $booking = GoldBooking::with('certificate')->findOrFail($id);
        $certificate = $booking->certificate;

        if (!$certificate || !$certificate->pdf_path || !Storage::disk('public')->exists($certificate->pdf_path)) {
            return back()->with('error', 'Price Lock Certificate PDF not found.');
        }

        // Log certificate download activity
        $this->logActivityDirect('certificate_downloaded', "Price Lock Certificate {$certificate->certificate_number} downloaded for Booking {$booking->booking_number}", $booking->id);

        return Storage::disk('public')->download($certificate->pdf_path, "Price_Lock_Certificate_{$booking->booking_number}.pdf");
    }

    /**
     * Change Booking Status
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Draft,Booked,Active,Completed,Cancelled,Refund Initiated,Refunded',
            'remarks' => 'nullable|string',
        ]);

        $booking = GoldBooking::findOrFail($id);
        $this->bookingService->changeStatus($booking, $request->status, $request->remarks);

        return back()->with('success', 'Booking status updated successfully.');
    }

    /**
     * Export Bookings list as CSV
     */
    public function exportCsv(Request $request)
    {
        $query = GoldBooking::with(['customer', 'product', 'emiPlan'])->latest();

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function ($custQ) use ($search) {
                      $custQ->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('product', function ($prodQ) use ($search) {
                      $prodQ->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('booking_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $bookings = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Gold_Bookings_Report_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Booking Number', 'Customer Name', 'Customer Email', 'Product Name', 'Gold Weight (g)', 'Locked Gold Price (₹/g)', 'Monthly EMI (₹)', 'Grand Total (₹)', 'Booking Date', 'Status'];

        $callback = function() use($bookings, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($bookings as $booking) {
                fputcsv($file, [
                    $booking->booking_number,
                    $booking->customer->name ?? 'N/A',
                    $booking->customer->email ?? 'N/A',
                    $booking->product->name ?? 'N/A',
                    number_format($booking->gold_weight, 2),
                    number_format($booking->locked_price_per_gram, 2),
                    number_format($booking->monthly_emi, 2),
                    number_format($booking->grand_total, 2),
                    $booking->booking_date->format('Y-m-d H:i:s'),
                    $booking->status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Direct logging inside ActivityLog schema
     */
    protected function logActivityDirect($action, $description, $recordId)
    {
        $userAgent = request()->header('User-Agent');
        $browser = 'Unknown';
        if (!empty($userAgent)) {
            if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) $browser = 'Internet Explorer';
            elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
            elseif (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
            elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
            elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) $browser = 'Opera';
            elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
        }

        \App\Models\ActivityLog::create([
            'module_name' => 'gold_booking',
            'record_id' => $recordId,
            'action_type' => $action,
            'old_data' => null,
            'new_data' => null,
            'description' => $description,
            'created_by_id' => auth()->id() ?? 1,
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
