<?php

namespace App\Http\Controllers;

use App\Models\GoldBooking;
use App\Models\Product;
use App\Models\EmiPlan;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\BookingService;
use App\Services\EmiCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoldBookingController extends Controller
{
    protected $bookingService;
    protected $emiService;

    public function __construct(BookingService $bookingService, EmiCalculationService $emiService)
    {
        $this->bookingService = $bookingService;
        $this->emiService = $emiService;
    }

    /**
     * Show bookings list with filters, search, and pagination
     */
    public function index(Request $request)
    {
        $query = GoldBooking::with(['customer', 'product', 'emiPlan'])->latest();

        // 1. Filter by Search Query
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function ($custQ) use ($search) {
                      $custQ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('product', function ($prodQ) use ($search) {
                      $prodQ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('sku', 'like', '%' . $search . '%');
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

        // 4. Filter by Date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('booking_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $bookings = $query->paginate(10)->withQueryString();
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
        ]);

        try {
            $booking = $this->bookingService->createBooking(
                $request->customer_id,
                $request->product_id,
                $request->emi_plan_id,
                $request->remarks
            );

            return response()->json([
                'success' => true,
                'message' => 'Gold Booking created successfully! Price Locked.',
                'redirect_url' => route('bookings.show', $booking->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Booking failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Show booking details panel
     */
    public function show($id)
    {
        $booking = GoldBooking::with(['customer', 'product', 'emiPlan', 'certificate', 'statusHistory.changedBy'])->findOrFail($id);
        
        // Amortization Repayment schedule based on locked gold values
        $schedule = $this->emiService->generateOutstandingSchedule($booking->emiPlan, $booking->locked_gold_value);

        // Fetch related activity logs
        $activityLogs = ActivityLog::where('module_name', 'gold_booking')
            ->where('record_id', $booking->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.bookings.show', compact('booking', 'schedule', 'activityLogs'));
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
            'status' => 'required|string|in:Draft,Pending First EMI,Active,Completed,Cancelled,Refund Initiated,Refunded',
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
