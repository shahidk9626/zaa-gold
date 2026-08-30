<?php

namespace App\Http\Controllers;

use App\Models\CustomerReferral;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReferralController extends Controller
{
    /**
     * Display listing of customer referrals.
     */
    public function index(Request $request)
    {
        $query = CustomerReferral::with(['referrer.staffDetail', 'customer.customerDetail', 'booking'])->latest('referred_at');

        // Date Range Filter (From Date / To Date)
        if ($request->filled('from_date') || $request->filled('start_date')) {
            $fromDateStr = $request->from_date ?? $request->start_date;
            try {
                $fromDate = Carbon::parse($fromDateStr)->startOfDay();
                $query->where('referred_at', '>=', $fromDate);
            } catch (\Exception $e) {
                // Ignore parse errors
            }
        }

        if ($request->filled('to_date') || $request->filled('end_date')) {
            $toDateStr = $request->to_date ?? $request->end_date;
            try {
                $toDate = Carbon::parse($toDateStr)->endOfDay();
                $query->where('referred_at', '<=', $toDate);
            } catch (\Exception $e) {
                // Ignore parse errors
            }
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search Query Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhere('payment_reference_number', 'like', "%{$search}%")
                  ->orWhereHas('referrer', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('referral_code', 'like', "%{$search}%")
                        ->orWhereHas('staffDetail', function ($sdq) use ($search) {
                            $sdq->where('emp_code', 'like', "%{$search}%");
                        });
                  })
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                  })
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', "%{$search}%");
                  });
            });
        }

        $referrals = $query->paginate(20)->withQueryString();

        // Calculate summary stats
        $totalReferrals = CustomerReferral::count();
        $pendingCount = CustomerReferral::where('status', 'Pending')->count();
        $underReviewCount = CustomerReferral::where('status', 'Under Review')->count();
        $approvedCount = CustomerReferral::where('status', 'Approved')->count();
        $completedCount = CustomerReferral::where('status', 'Completed')->count();
        $rejectedCount = CustomerReferral::where('status', 'Rejected')->count();

        $totalCashback = CustomerReferral::sum('cashback_amount');
        $pendingCashbackSum = CustomerReferral::whereIn('status', ['Pending', 'Under Review', 'Approved'])->sum('cashback_amount');
        $completedCashbackSum = CustomerReferral::where('status', 'Completed')->sum('cashback_amount');

        return view('admin.referrals.index', compact(
            'referrals',
            'totalReferrals',
            'pendingCount',
            'underReviewCount',
            'approvedCount',
            'completedCount',
            'rejectedCount',
            'totalCashback',
            'pendingCashbackSum',
            'completedCashbackSum'
        ));
    }

    /**
     * Show detailed referral view.
     */
    public function show($id)
    {
        $referral = CustomerReferral::with(['referrer.staffDetail', 'customer.customerDetail', 'booking.emiPlan', 'booking.product', 'processor'])->findOrFail($id);

        $activityLogs = ActivityLog::where('module_name', 'referral')
            ->where('record_id', $referral->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.referrals.show', compact('referral', 'activityLogs'));
    }

    /**
     * Show create form for new referral mapping.
     */
    public function create()
    {
        abort_unless(hasPermission('referral.edit'), 403);

        $customers = User::where('status', 'active')
            ->whereHas('role', function($q) {
                $q->where('slug', 'customer');
            })
            ->get();

        return view('admin.referrals.create', compact('customers'));
    }

    /**
     * Store new referral mapping.
     */
    public function store(Request $request)
    {
        abort_unless(hasPermission('referral.edit'), 403);

        $request->validate([
            'referral_code' => 'required|string|max:50',
            'referrer_customer_id' => 'required|exists:users,id',
            'referred_customer_id' => 'required|exists:users,id',
            'remarks' => 'nullable|string',
        ]);

        CustomerReferral::create([
            'referrer_id' => $request->referrer_customer_id,
            'referrer_type' => 'customer',
            'customer_id' => $request->referred_customer_id,
            'referral_code' => $request->referral_code,
            'referred_at' => now(),
            'status' => 'Pending',
            'admin_remark' => $request->remarks,
        ]);

        return redirect()->route('referrals.index')->with('success', 'Referral created successfully.');
    }

    /**
     * Show edit form for referral status update.
     */
    public function edit($id)
    {
        abort_unless(hasPermission('referral.edit'), 403);

        $referral = CustomerReferral::with(['referrer', 'customer'])->findOrFail($id);

        // Fetch active bookings for the referred customer
        $bookings = \App\Models\GoldBooking::where('customer_id', $referral->customer_id)
            ->whereIn('status', ['Booked', 'Active', 'Completed'])
            ->get();

        return view('admin.referrals.edit', compact('referral', 'bookings'));
    }

    /**
     * Update referral status and associate booking.
     */
    public function update(Request $request, $id)
    {
        abort_unless(hasPermission('referral.edit'), 403);

        $referral = CustomerReferral::findOrFail($id);

        $request->validate([
            'status' => 'required|in:Pending,Under Review,Approved,Completed,Rejected',
            'booking_id' => 'nullable|exists:gold_bookings,id',
            'payment_reference_number' => 'required_if:status,Completed|nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $oldStatus = $referral->status;
        $newStatus = $request->status;

        $updateData = [
            'status' => $newStatus,
            'booking_id' => $request->booking_id,
            'admin_remark' => $request->remarks,
        ];

        if ($newStatus === 'Completed') {
            $updateData['payment_reference_number'] = $request->payment_reference_number;
            $updateData['processed_by'] = auth()->id();
            $updateData['completed_at'] = now();
        } else {
            if ($oldStatus === 'Completed') {
                $updateData['payment_reference_number'] = null;
                $updateData['processed_by'] = null;
                $updateData['completed_at'] = null;
            }
        }

        // If booking is selected and changed, let's update snapshot weights & amount
        if ($request->booking_id && $request->booking_id != $referral->booking_id) {
            $booking = \App\Models\GoldBooking::find($request->booking_id);
            if ($booking) {
                $updateData['gold_weight'] = $booking->gold_weight;
                $rate = $referral->cashback_rate ?: (float) \App\Models\SystemSetting::get('referral_cashback_rate', 500.00);
                $updateData['cashback_rate'] = $rate;
                $updateData['cashback_amount'] = $booking->gold_weight * $rate;
            }
        }

        $referral->update($updateData);

        // Log action in ActivityLog
        ActivityLog::create([
            'module_name' => 'referral',
            'record_id' => $referral->id,
            'action_type' => 'referral_updated',
            'description' => "Referral status updated from {$oldStatus} to {$newStatus} for referral #{$referral->id}.",
            'created_by_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('referrals.show', $referral->id)
            ->with('success', 'Referral updated successfully.');
    }

    /**
     * Export filtered list to CSV.
     */
    public function exportCsv(Request $request)
    {
        $query = CustomerReferral::with(['referrer.staffDetail', 'customer.customerDetail', 'booking'])->latest('referred_at');

        if ($request->filled('from_date') || $request->filled('start_date')) {
            $fromDateStr = $request->from_date ?? $request->start_date;
            try {
                $fromDate = Carbon::parse($fromDateStr)->startOfDay();
                $query->where('referred_at', '>=', $fromDate);
            } catch (\Exception $e) {}
        }

        if ($request->filled('to_date') || $request->filled('end_date')) {
            $toDateStr = $request->to_date ?? $request->end_date;
            try {
                $toDate = Carbon::parse($toDateStr)->endOfDay();
                $query->where('referred_at', '<=', $toDate);
            } catch (\Exception $e) {}
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhere('payment_reference_number', 'like', "%{$search}%")
                  ->orWhereHas('referrer', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('referral_code', 'like', "%{$search}%")
                        ->orWhereHas('staffDetail', function ($sdq) use ($search) {
                            $sdq->where('emp_code', 'like', "%{$search}%");
                        });
                  })
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                  })
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', "%{$search}%");
                  });
            });
        }

        $referrals = $query->get();

        $fileName = "Customer_Referrals_Report_" . date('Y-m-d_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'Sr. No.',
            'Referral Date',
            'Referral Code Used',
            'Referrer Name',
            'Referrer Type',
            'Referrer Code',
            'Customer Name',
            'Customer ID',
            'Customer Mobile',
            'Customer Email',
            'Booking ID',
            'Gold Weight (g)',
            'Cashback Rate (Rs)',
            'Cashback Amount (Rs)',
            'Referral Status',
            'Payment Reference Number',
            'Completed Date'
        ];

        $callback = function() use ($referrals, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($referrals as $index => $ref) {
                $refType = $ref->referrer_type ? ucfirst($ref->referrer_type) : ($ref->staff_id ? 'Staff' : 'Customer');
                $refCode = $ref->referrer->referral_code ?? ($ref->referrer->staffDetail->emp_code ?? 'N/A');
                fputcsv($file, [
                    $index + 1,
                    $ref->referred_at ? $ref->referred_at->format('d/m/Y H:i:s') : 'N/A',
                    $ref->referral_code,
                    $ref->referrer->name ?? 'N/A',
                    $refType,
                    $refCode,
                    $ref->customer->name ?? 'N/A',
                    $ref->customer->id ?? 'N/A',
                    $ref->customer->phone ?? 'N/A',
                    $ref->customer->email ?? 'N/A',
                    $ref->booking->booking_number ?? 'N/A',
                    $ref->gold_weight,
                    $ref->cashback_rate,
                    $ref->cashback_amount,
                    $ref->status,
                    $ref->payment_reference_number ?? 'N/A',
                    $ref->completed_at ? $ref->completed_at->format('d/m/Y H:i:s') : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
