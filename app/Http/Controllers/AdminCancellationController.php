<?php

namespace App\Http\Controllers;

use App\Models\CancellationRequest;
use App\Models\GoldBooking;
use App\Services\CancellationService;
use App\Services\RefundService;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCancellationController extends Controller
{
    protected $cancellationService;
    protected $refundService;
    protected $customerService;

    public function __construct(
        CancellationService $cancellationService,
        RefundService $refundService,
        CustomerService $customerService
    ) {
        $this->cancellationService = $cancellationService;
        $this->refundService = $refundService;
        $this->customerService = $customerService;
    }

    /**
     * List all cancellation requests (with filters)
     */
    public function index(Request $request)
    {
        $this->authorizeAction('cancellations.view');

        $query = CancellationRequest::with(['booking.emiPlan', 'customer']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($inner) use ($search) {
                      $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('booking', function ($inner) use ($search) {
                      $inner->where('booking_number', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest('id')->paginate(15)->withQueryString();

        return view('admin.cancellations.index', compact('requests'));
    }

    /**
     * Show cancellation request details page
     */
    public function show($id)
    {
        $this->authorizeAction('cancellations.view');

        $cancellationRequest = CancellationRequest::with([
            'customer.customerDetail',
            'booking.product',
            'booking.emiPlan',
            'approvedBy'
        ])->findOrFail($id);

        $booking = $cancellationRequest->booking;

        // Fetch related details for booking
        $schedule = $booking->schedules()->orderBy('installment_number')->get();
        $payments = $booking->payments()->with('emiSchedule')->latest('payment_date')->get();
        $receipts = $payments->where('status', 'Paid');

        // Fetch enrichment financials from CustomerService
        $financials = $this->customerService->getFinancialSummary($booking, $receipts);

        // Fetch activity logs
        $logs = \App\Models\ActivityLog::where('module_name', 'gold_booking')
            ->where('record_id', $booking->id)
            ->latest()
            ->get();

        return view('admin.cancellations.show', compact('cancellationRequest', 'booking', 'schedule', 'payments', 'receipts', 'financials', 'logs'));
    }

    /**
     * Handle Admin Action: Approve, Reject, Retain, or Review
     */
    public function processAction(Request $request, $id)
    {
        $cancellationRequest = CancellationRequest::findOrFail($id);

        $request->validate([
            'action' => 'required|in:approve,reject,retain,review',
            'admin_remark' => 'required|string|max:1000',
        ]);

        $action = $request->action;
        $remark = $request->admin_remark;

        // Map request action to internal statuses
        $statusMap = [
            'approve' => 'Approved',
            'reject' => 'Rejected',
            'retain' => 'Customer Retained',
            'review' => 'Under Review'
        ];

        $newStatus = $statusMap[$action];

        // Check appropriate permission
        if ($newStatus === 'Approved') {
            $this->authorizeAction('cancellations.approve');
        } elseif ($newStatus === 'Rejected') {
            $this->authorizeAction('cancellations.reject');
        } else {
            $this->authorizeAction('cancellations.view');
        }

        try {
            $this->cancellationService->updateStatus($cancellationRequest, $newStatus, $remark);
            return back()->with('success', "Cancellation request marked as '{$newStatus}' successfully.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Handle Admin Action: Initiate Refund
     */
    public function initiateRefund(Request $request, $id)
    {
        $this->authorizeAction('cancellations.refund');
        $cancellationRequest = CancellationRequest::findOrFail($id);

        $request->validate([
            'admin_remark' => 'required|string|max:1000',
        ]);

        try {
            $this->refundService->initiateRefund($cancellationRequest, $request->admin_remark);
            return back()->with('success', 'Refund process has been marked as initiated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Handle Admin Action: Complete Refund
     */
    public function completeRefund(Request $request, $id)
    {
        $this->authorizeAction('cancellations.refund');
        $cancellationRequest = CancellationRequest::findOrFail($id);

        $request->validate([
            'refund_transaction_number' => 'required|string|max:100',
            'refund_mode' => 'required|string|max:50',
            'refund_date' => 'required|date',
            'admin_remark' => 'required|string|max:1000',
        ]);

        try {
            $this->refundService->completeRefund(
                $cancellationRequest,
                $request->refund_transaction_number,
                $request->refund_mode,
                $request->refund_date,
                $request->admin_remark
            );
            return back()->with('success', 'Refund has been completed and transaction details updated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Check if authenticated user has permission
     */
    protected function authorizeAction(string $permission)
    {
        if (Auth::user()->role === 'super-admin') {
            return;
        }

        // Standard Laravel check
        if (!Auth::user()->can($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
