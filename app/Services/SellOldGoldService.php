<?php

namespace App\Services;

use App\Models\SellOldGoldEnquiry;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class SellOldGoldService
{
    /**
     * Get paginated old gold enquiries with filters
     */
    public function getFilteredEnquiries(array $filters, int $perPage = 10)
    {
        $query = SellOldGoldEnquiry::with(['assignedStaff'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', '%' . $search . '%')
                  ->orWhere('mobile', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('city', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get list of staff/admins that enquiries can be assigned to
     */
    public function getAssigneeList()
    {
        return User::whereHas('role', function($q) {
            $q->whereIn('slug', ['super-admin', 'admin', 'staff']);
        })->where('status', 'active')->orderBy('name')->get();
    }

    /**
     * Update enquiry assignee
     */
    public function assignEnquiry(SellOldGoldEnquiry $enquiry, $staffId)
    {
        $oldAssigned = $enquiry->assignedStaff;
        $enquiry->assigned_to = $staffId;
        $enquiry->save();

        $newAssigned = User::find($staffId);
        $newAssignedName = $newAssigned ? $newAssigned->name : 'Unassigned';
        $oldAssignedName = $oldAssigned ? $oldAssigned->name : 'None';

        $this->logDirectActivity(
            'sell_old_gold_enquiry',
            $enquiry->id,
            'assigned',
            "Enquiry assigned to {$newAssignedName} (previously: {$oldAssignedName})"
        );
    }

    /**
     * Update enquiry status
     */
    public function updateStatus(SellOldGoldEnquiry $enquiry, $status, $remarks = null)
    {
        $oldStatus = $enquiry->status;
        $enquiry->status = $status;
        if ($remarks) {
            $enquiry->remarks = $remarks;
        }
        $enquiry->save();

        $this->logDirectActivity(
            'sell_old_gold_enquiry',
            $enquiry->id,
            'status_updated',
            "Status updated from {$oldStatus} to {$status}." . ($remarks ? " Remarks: {$remarks}" : "")
        );
    }

    /**
     * Add internal note to timeline
     */
    public function addInternalNote(SellOldGoldEnquiry $enquiry, $note)
    {
        $this->logDirectActivity(
            'sell_old_gold_enquiry',
            $enquiry->id,
            'internal_note',
            "Internal Note: " . $note
        );
    }

    /**
     * Fetch timeline logs (activity logs) for enquiry
     */
    public function getTimeline(SellOldGoldEnquiry $enquiry)
    {
        return ActivityLog::where('module_name', 'sell_old_gold_enquiry')
            ->where('record_id', $enquiry->id)
            ->with('user')
            ->latest()
            ->get();
    }

    /**
     * Helper to log activity directly
     */
    protected function logDirectActivity($module, $recordId, $action, $description)
    {
        $userAgent = Request::header('User-Agent');
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
            'module_name' => $module,
            'record_id' => $recordId,
            'action_type' => $action,
            'description' => $description,
            'created_by_id' => Auth::id() ?? 1,
            'ip_address' => Request::ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
