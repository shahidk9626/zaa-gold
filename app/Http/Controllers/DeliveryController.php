<?php

namespace App\Http\Controllers;

use App\Models\BookingDelivery;
use App\Models\GoldBooking;
use App\Models\ActivityLog;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    protected $deliveryService;

    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Display deliveries list
     */
    public function index(Request $request)
    {
        $query = BookingDelivery::with(['booking.customer', 'booking.product'])->latest('request_date');

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('delivery_number', 'like', '%' . $search . '%')
                  ->orWhere('receiver_name', 'like', '%' . $search . '%')
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('delivery_status', $request->status);
        }

        // Method Filter
        if ($request->filled('method')) {
            $query->where('delivery_method', $request->method);
        }

        $deliveries = $query->paginate(20)->withQueryString();

        return view('admin.deliveries.index', compact('deliveries'));
    }

    /**
     * Show delivery details
     */
    public function show($id)
    {
        $delivery = BookingDelivery::with(['booking.customer', 'booking.product', 'statusHistories.changedBy'])->findOrFail($id);

        $activityLogs = ActivityLog::where('module_name', 'gold_booking')
            ->where('record_id', $delivery->booking_id)
            ->whereIn('action_type', ['delivery_requested', 'delivery_approved', 'otp_generated', 'otp_verified', 'delivery_dispatched', 'delivery_completed', 'delivery_cancelled'])
            ->with('user')
            ->latest()
            ->get();

        return view('admin.deliveries.show', compact('delivery', 'activityLogs'));
    }

    /**
     * Store new delivery request
     */
    public function storeRequest($bookingId, Request $request)
    {
        $booking = GoldBooking::findOrFail($bookingId);

        $rules = [
            'delivery_method' => 'required|in:Office Pickup,Courier,Branch Pickup',
            'remarks' => 'nullable|string|max:500'
        ];

        if ($request->delivery_method === 'Courier') {
            $rules['delivery_address'] = 'required|string|max:500';
        } elseif ($request->delivery_method === 'Branch Pickup') {
            $rules['pickup_branch'] = 'required|string|max:100';
            $rules['pickup_date'] = 'required|date|after_or_equal:today';
            $rules['pickup_time'] = 'required';
        }

        $request->validate($rules);

        try {
            $delivery = $this->deliveryService->requestDelivery($booking, $request->all());
            return redirect()->route('bookings.show', $bookingId)->with('success', "Delivery request {$delivery->delivery_number} created successfully.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve Delivery request
     */
    public function approve($id, Request $request)
    {
        $delivery = BookingDelivery::findOrFail($id);

        try {
            $this->deliveryService->approveDelivery($delivery, $request->all());
            return back()->with('success', "Delivery {$delivery->delivery_number} approved and challan generated.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Dispatch Delivery (Courier tracking)
     */
    public function dispatchDelivery($id, Request $request)
    {
        $delivery = BookingDelivery::findOrFail($id);

        if ($delivery->delivery_method === 'Courier') {
            $request->validate([
                'courier_partner' => 'required|string|max:100',
                'tracking_number' => 'required|string|max:100',
                'tracking_url' => 'nullable|url'
            ]);
        }

        try {
            $this->deliveryService->dispatchDelivery($delivery, $request->all());
            return back()->with('success', "Delivery {$delivery->delivery_number} marked as Dispatched.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete Delivery (OTP verification)
     */
    public function complete($id, Request $request)
    {
        $delivery = BookingDelivery::findOrFail($id);

        $rules = [
            'receiver_name' => 'nullable|string|max:100',
            'receiver_mobile' => 'nullable|string|max:20',
            'receiver_id_proof' => 'nullable|string|max:100'
        ];

        if (in_array($delivery->delivery_method, ['Office Pickup', 'Branch Pickup'])) {
            $rules['otp'] = 'required|string|size:6';
        }

        $request->validate($rules);

        try {
            $this->deliveryService->completeDelivery($delivery, $request->all());
            return back()->with('success', "Delivery {$delivery->delivery_number} completed successfully. Gold handed over.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel delivery request
     */
    public function cancel($id, Request $request)
    {
        $request->validate([
            'remarks' => 'required|string|max:500'
        ]);

        $delivery = BookingDelivery::findOrFail($id);

        try {
            $this->deliveryService->cancelDelivery($delivery, $request->remarks);
            return back()->with('success', "Delivery request {$delivery->delivery_number} has been cancelled.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Regenerate OTP
     */
    public function regenerateOtp($id)
    {
        $delivery = BookingDelivery::findOrFail($id);

        try {
            $this->deliveryService->regenerateOtp($delivery);
            return back()->with('success', 'A new 6-digit OTP code has been generated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Download Delivery Challan PDF
     */
    public function downloadChallan($id)
    {
        $delivery = BookingDelivery::findOrFail($id);

        if (empty($delivery->pdf_path) || !Storage::disk('public')->exists($delivery->pdf_path)) {
            $pdfPath = $this->deliveryService->generateDeliveryChallanPdf($delivery);
            $delivery->pdf_path = $pdfPath;
            $delivery->save();
        }

        // Log challan download activity
        $this->logActivityDirect('delivery_downloaded', "Delivery challan {$delivery->delivery_number} downloaded", $delivery->booking_id);

        return Storage::disk('public')->download($delivery->pdf_path, "Challan_{$delivery->delivery_number}.pdf");
    }

    /**
     * Export Delivery list as CSV
     */
    public function exportCsv(Request $request)
    {
        $query = BookingDelivery::with(['booking', 'customer'])->latest('request_date');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('delivery_number', 'like', '%' . $search . '%')
                  ->orWhere('receiver_name', 'like', '%' . $search . '%')
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('delivery_status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('delivery_method', $request->method);
        }

        $deliveries = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Deliveries_Export_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Delivery Number', 'Booking Number', 'Customer', 'Delivery Method', 'Status', 'Request Date', 'Receiver Name', 'Receiver Mobile', 'Remarks'];

        $callback = function() use($deliveries, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($deliveries as $delivery) {
                fputcsv($file, [
                    $delivery->delivery_number,
                    $delivery->booking->booking_number,
                    $delivery->customer->name,
                    $delivery->delivery_method,
                    $delivery->delivery_status,
                    $delivery->request_date ? $delivery->request_date->format('Y-m-d H:i:s') : '',
                    $delivery->receiver_name ?? '—',
                    $delivery->receiver_mobile ?? '—',
                    $delivery->remarks ?? ''
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

        ActivityLog::create([
            'module_name' => 'gold_booking',
            'record_id' => $recordId,
            'action_type' => $action,
            'old_data' => null,
            'new_data' => null,
            'description' => $description,
            'created_by_id' => Auth::id() ?? 1,
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
