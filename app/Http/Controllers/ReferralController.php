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
        $query = CustomerReferral::with(['staff.staffDetail', 'customer.customerDetail'])->latest('referred_at');

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

        // Search Query Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhereHas('staff', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('staffDetail', function ($sdq) use ($search) {
                            $sdq->where('emp_code', 'like', "%{$search}%");
                        });
                  })
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                  });
            });
        }

        $referrals = $query->paginate(20)->withQueryString();

        return view('admin.referrals.index', compact('referrals'));
    }

    /**
     * Show detailed referral view.
     */
    public function show($id)
    {
        $referral = CustomerReferral::with(['staff.staffDetail', 'customer.customerDetail'])->findOrFail($id);

        $activityLogs = ActivityLog::where('module_name', 'referral')
            ->where('record_id', $referral->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.referrals.show', compact('referral', 'activityLogs'));
    }

    /**
     * Export filtered list to CSV.
     */
    public function exportCsv(Request $request)
    {
        $query = CustomerReferral::with(['staff.staffDetail', 'customer.customerDetail'])->latest('referred_at');

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

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhereHas('staff', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('staffDetail', function ($sdq) use ($search) {
                            $sdq->where('emp_code', 'like', "%{$search}%");
                        });
                  })
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('id', $search);
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
            'Referral Code',
            'Employee/Staff Name',
            'Employee/Staff Code',
            'Customer Name',
            'Customer ID',
            'Customer Mobile',
            'Customer Email',
            'Customer Registration Date',
            'Customer Status'
        ];

        $callback = function() use ($referrals, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($referrals as $index => $ref) {
                fputcsv($file, [
                    $index + 1,
                    $ref->referred_at ? $ref->referred_at->format('d/m/Y H:i:s') : 'N/A',
                    $ref->referral_code,
                    $ref->staff->name ?? 'N/A',
                    $ref->staff->staffDetail->emp_code ?? 'N/A',
                    $ref->customer->name ?? 'N/A',
                    $ref->customer->id ?? 'N/A',
                    $ref->customer->phone ?? 'N/A',
                    $ref->customer->email ?? 'N/A',
                    $ref->customer && $ref->customer->created_at ? $ref->customer->created_at->format('d/m/Y H:i:s') : 'N/A',
                    ucfirst($ref->customer->status ?? 'active'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
