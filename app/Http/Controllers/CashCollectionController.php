<?php

namespace App\Http\Controllers;

use App\Models\CashCollectionRequest;
use App\Models\GoldBooking;
use App\Models\BookingPayment;
use App\Models\BookingEmiSchedule;
use App\Models\PaymentTransaction;
use App\Models\ActivityLog;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashCollectionController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(Request $request)
    {
        $query = CashCollectionRequest::with(['booking', 'transaction', 'customer', 'collectedBy', 'verifiedBy'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('collection_number', 'like', '%' . $search . '%')
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('transaction', function ($tq) use ($search) {
                      $tq->where('transaction_number', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->ajax()) {
            $requests = $query->get()->map(function ($ccr) {
                return [
                    'id' => $ccr->id,
                    'collection_number' => $ccr->collection_number,
                    'transaction_number' => $ccr->transaction->transaction_number ?? 'N/A',
                    'booking_number' => $ccr->booking->booking_number ?? 'N/A',
                    'customer_name' => $ccr->customer->name ?? 'N/A',
                    'collected_by' => $ccr->collectedBy->name ?? 'N/A',
                    'amount' => '₹' . number_format($ccr->amount, 2),
                    'payment_type' => 'Cash',
                    'booking_date' => $ccr->booking?->booking_date?->format('d M Y') ?? 'N/A',
                    'collection_date' => $ccr->collection_date?->format('d M Y') ?? 'N/A',
                    'status' => $ccr->status,
                    'remarks' => $ccr->remarks ?? '',
                    'verified_by' => $ccr->verifiedBy->name ?? 'N/A',
                    'verified_at' => $ccr->verified_at ? $ccr->verified_at->format('d M Y, h:i A') : 'N/A',
                    'view_url' => route('admin.cash-collections.show', $ccr->id),
                ];
            });
            return response()->json(['data' => $requests]);
        }

        $requests = $query->paginate(15)->withQueryString();
        return view('admin.cash-collections.index', compact('requests'));
    }

    public function show($id)
    {
        $ccr = CashCollectionRequest::with([
            'booking.product', 
            'booking.emiPlan', 
            'transaction', 
            'customer', 
            'receipt', 
            'collectedBy', 
            'verifiedBy'
        ])->findOrFail($id);

        $timeline = ActivityLog::where('module_name', 'gold_booking')
            ->where('record_id', $ccr->booking_id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.cash-collections.show', compact('ccr', 'timeline'));
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'remark' => 'required|string|max:1000',
        ]);

        $ccr = CashCollectionRequest::findOrFail($id);

        if ($ccr->status !== 'Pending Verification') {
            return back()->with('error', 'This cash collection is already processed.');
        }

        DB::transaction(function () use ($ccr, $request) {
            $now = now();
            $adminId = Auth::id();

            // 1. Update CCR
            $ccr->update([
                'status' => 'Verified',
                'verified_by_id' => $adminId,
                'verified_at' => $now,
                'remarks' => $request->remark,
            ]);

            // 2. Update BookingPayment (Receipt)
            $receipt = BookingPayment::find($ccr->payment_id);
            if ($receipt) {
                $receipt->update([
                    'status' => 'Paid',
                    'remarks' => $request->remark,
                ]);

                // Also update the linked EMI Schedule status to Paid
                if ($receipt->emi_schedule_id) {
                    $firstEmi = BookingEmiSchedule::find($receipt->emi_schedule_id);
                    if ($firstEmi) {
                        $firstEmi->update([
                            'status' => 'Paid',
                            'paid_at' => $now,
                        ]);
                    }
                }
            }

            // 3. Update GoldBooking status to Paid
            $booking = GoldBooking::find($ccr->booking_id);
            if ($booking) {
                $booking->status = 'Paid';
                $booking->status_change_remarks = 'Booking status updated to Paid upon Cash verification.';
                $booking->save();
            }

            // 4. Update PaymentTransaction
            $transaction = PaymentTransaction::find($ccr->transaction_id);
            if ($transaction) {
                $transaction->update([
                    'payment_status' => 'Success',
                    'verified_at' => $now,
                    'paid_at' => $now,
                ]);
            }

            // 5. Generate Invoice
            if ($receipt) {
                $this->invoiceService->generateInvoice($receipt);
            }

            // 6. Log activity
            $this->logActivity('cash_verified', "Cash collection request {$ccr->collection_number} verified and approved.", $ccr->booking_id);
        });

        return redirect()->route('admin.cash-collections.show', $ccr->id)
            ->with('success', 'Cash payment verified and approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $ccr = CashCollectionRequest::findOrFail($id);

        if ($ccr->status !== 'Pending Verification') {
            return back()->with('error', 'This cash collection is already processed.');
        }

        DB::transaction(function () use ($ccr, $request) {
            $now = now();
            $adminId = Auth::id();

            // 1. Update CCR
            $ccr->update([
                'status' => 'Rejected',
                'verified_by_id' => $adminId,
                'verified_at' => $now,
                'remarks' => $request->reason,
            ]);

            // 2. Update BookingPayment (Receipt) to Rejected
            $receipt = BookingPayment::find($ccr->payment_id);
            if ($receipt) {
                $receipt->update([
                    'status' => 'Rejected',
                    'remarks' => $request->reason,
                ]);
            }

            // 3. Update PaymentTransaction to Rejected
            $transaction = PaymentTransaction::find($ccr->transaction_id);
            if ($transaction) {
                $transaction->update([
                    'payment_status' => 'Rejected',
                    'failure_reason' => $request->reason,
                    'verified_at' => $now,
                ]);
            }

            // 4. Log activity
            $this->logActivity('cash_rejected', "Cash collection request {$ccr->collection_number} rejected. Reason: {$request->reason}", $ccr->booking_id);
        });

        return redirect()->route('admin.cash-collections.show', $ccr->id)
            ->with('success', 'Cash payment rejected successfully.');
    }

    protected function logActivity($action, $description, $recordId)
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
            'description' => $description,
            'created_by_id' => Auth::id() ?? 1,
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
