<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfileUpdateRequest;
use App\Models\User;
use App\Models\CustomerDetail;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminProfileUpdateRequestController extends Controller
{
    /**
     * Display listing of customer profile update requests.
     */
    public function index(Request $request)
    {
        $query = CustomerProfileUpdateRequest::with(['customer.customerDetail', 'reviewer'])->latest();

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date Range Filter
        if ($request->filled('from_date') || $request->filled('start_date')) {
            $fromDateStr = $request->from_date ?? $request->start_date;
            try {
                $fromDate = Carbon::parse($fromDateStr)->startOfDay();
                $query->where('created_at', '>=', $fromDate);
            } catch (\Exception $e) {}
        }

        if ($request->filled('to_date') || $request->filled('end_date')) {
            $toDateStr = $request->to_date ?? $request->end_date;
            try {
                $toDate = Carbon::parse($toDateStr)->endOfDay();
                $query->where('created_at', '<=', $toDate);
            } catch (\Exception $e) {}
        }

        // Search Query Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('id', $search);
                  });
            });
        }

        $requests = $query->paginate(20)->withQueryString();

        return view('admin.profile-update-requests.index', compact('requests'));
    }

    /**
     * Show detailed request view.
     */
    public function show($id)
    {
        $profileRequest = CustomerProfileUpdateRequest::with(['customer.customerDetail', 'reviewer'])->findOrFail($id);

        $activityLogs = ActivityLog::where('module_name', 'customer_profile_update_request')
            ->where('record_id', $profileRequest->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.profile-update-requests.show', compact('profileRequest', 'activityLogs'));
    }

    /**
     * Approve a pending profile update request.
     */
    public function approve(Request $request, $id)
    {
        $profileRequest = CustomerProfileUpdateRequest::findOrFail($id);

        // Immutable status check
        if ($profileRequest->status !== 'Pending') {
            return back()->with('error', 'This profile update request has already been processed and cannot be modified.');
        }

        $customer = User::with('customerDetail')->findOrFail($profileRequest->customer_id);

        try {
            DB::beginTransaction();

            $userUpdates = [];
            $detailUpdates = [];

            $requestedChanges = $profileRequest->requested_changes ?? [];

            foreach ($requestedChanges as $change) {
                $fieldName = $change['field_name'] ?? '';
                $newValue = $change['requested_value'] ?? null;

                if (!$fieldName || $newValue === null) continue;

                if (in_array($fieldName, ['name', 'email', 'phone', 'whatsapp_number'])) {
                    $userUpdates[$fieldName] = $newValue;
                } else {
                    $detailUpdates[$fieldName] = $newValue;
                }
            }

            // Apply User updates
            if (!empty($userUpdates)) {
                $customer->update($userUpdates);
            }

            // Apply Customer Detail updates
            if (!empty($detailUpdates)) {
                if ($customer->customerDetail) {
                    $customer->customerDetail->update($detailUpdates);
                } else {
                    CustomerDetail::create(array_merge($detailUpdates, [
                        'user_id' => $customer->id,
                        'slug' => \Illuminate\Support\Str::slug($customer->name . '-' . \Illuminate\Support\Str::random(5)),
                    ]));
                }
            }

            // Mark request as Approved
            $profileRequest->update([
                'status' => 'Approved',
                'admin_remark' => $request->admin_remark ? trim($request->admin_remark) : 'Approved by compliance admin.',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            DB::commit();

            $this->logActivity(
                $profileRequest->id,
                'approved',
                "Approved profile update request #{$profileRequest->id} for customer {$customer->name} (ID: {$customer->id})."
            );

            return redirect()->route('admin.profile-update-requests.show', $profileRequest->id)
                ->with('success', 'Profile update request approved successfully and customer profile has been updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'An error occurred while approving request: ' . $e->getMessage());
        }
    }

    /**
     * Reject a pending profile update request.
     */
    public function reject(Request $request, $id)
    {
        $profileRequest = CustomerProfileUpdateRequest::findOrFail($id);

        // Immutable status check
        if ($profileRequest->status !== 'Pending') {
            return back()->with('error', 'This profile update request has already been processed and cannot be modified.');
        }

        $request->validate([
            'admin_remark' => 'required|string|max:1000',
        ], [
            'admin_remark.required' => 'Please provide a reason for rejecting this request.',
        ]);

        $profileRequest->update([
            'status' => 'Rejected',
            'admin_remark' => trim($request->admin_remark),
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->logActivity(
            $profileRequest->id,
            'rejected',
            "Rejected profile update request #{$profileRequest->id} for customer ID {$profileRequest->customer_id}. Reason: {$request->admin_remark}"
        );

        return redirect()->route('admin.profile-update-requests.show', $profileRequest->id)
            ->with('success', 'Profile update request has been rejected.');
    }

    protected function logActivity($recordId, $action, $description)
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
            'module_name' => 'customer_profile_update_request',
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
