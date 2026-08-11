<?php

namespace App\Services;

use App\Models\WebsiteEnquiry;
use App\Models\ActivityLog;
use App\Mail\WebsiteEnquiryReceivedMail;
use App\Mail\WebsiteEnquiryAdminNotificationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class WebsiteEnquiryService
{
    /**
     * Get paginated website enquiries with filters.
     */
    public function getFilteredEnquiries(array $filters, int $perPage = 20)
    {
        $query = WebsiteEnquiry::with(['resolver'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('subject', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create an enquiry and safely dispatch email notifications.
     */
    public function createEnquiry(array $data): WebsiteEnquiry
    {
        DB::beginTransaction();
        try {
            $enquiry = WebsiteEnquiry::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'status' => WebsiteEnquiry::STATUS_NEW,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Dispatch acknowledgement email to customer
        try {
            Mail::to($enquiry->email)->queue(new WebsiteEnquiryReceivedMail($enquiry));
        } catch (\Exception $e) {
            Log::error("Failed to queue customer enquiry confirmation mail: " . $e->getMessage());
        }

        // Dispatch notification email to admin
        try {
            Mail::to('deshahid9626@gmail.com')->queue(new WebsiteEnquiryAdminNotificationMail($enquiry));
        } catch (\Exception $e) {
            Log::error("Failed to queue admin enquiry notification mail: " . $e->getMessage());
        }

        return $enquiry;
    }

    /**
     * Update enquiry status and resolve attributes.
     */
    public function updateStatus(WebsiteEnquiry $enquiry, string $status, ?string $remarks, int $userId): void
    {
        $oldStatus = $enquiry->status;
        $enquiry->status = $status;
        
        if ($remarks !== null) {
            $enquiry->admin_remark = $remarks;
        }

        if ($status === WebsiteEnquiry::STATUS_RESOLVED || $status === WebsiteEnquiry::STATUS_CLOSED) {
            $enquiry->resolved_at = now();
            $enquiry->resolved_by = $userId;
        } else {
            $enquiry->resolved_at = null;
            $enquiry->resolved_by = null;
        }

        $enquiry->save();

        // Write manual detailed activity log entry for the change
        $this->logDirectActivity(
            'website_enquiry',
            $enquiry->id,
            'status_updated',
            "Website Enquiry Status updated from '{$oldStatus}' to '{$status}'." . ($remarks ? " Remarks: {$remarks}" : "")
        );
    }

    /**
     * Fetch timeline logs (activity logs) for enquiry.
     */
    public function getTimeline(WebsiteEnquiry $enquiry)
    {
        return ActivityLog::where('module_name', 'website_enquiry')
            ->where('record_id', $enquiry->id)
            ->with('user')
            ->latest()
            ->get();
    }

    /**
     * Helper to log activity directly.
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
