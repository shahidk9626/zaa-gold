<?php

namespace App\Services;

use App\Models\GoldInspectionEnquiry;
use App\Models\ActivityLog;
use App\Mail\GoldInspectionAdminNotificationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class GoldInspectionEnquiryService
{
    /**
     * Get paginated inspection enquiries with filters.
     */
    public function getFilteredEnquiries(array $filters, int $perPage = 20)
    {
        $query = GoldInspectionEnquiry::with(['reviewer'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('id', 'like', '%' . $search . '%')
                  ->orWhere('gold_type', 'like', '%' . $search . '%')
                  ->orWhere('gold_location', 'like', '%' . $search . '%');
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
     * Create a new inspection enquiry and safely handle photos and email notifications.
     */
    public function createEnquiry(array $data): GoldInspectionEnquiry
    {
        DB::beginTransaction();
        try {
            $photoPaths = [];
            if (!empty($data['photos']) && is_array($data['photos'])) {
                foreach ($data['photos'] as $file) {
                    if ($file->isValid()) {
                        $photoPaths[] = $file->store('inspections/photos', 'public');
                    }
                }
            }

            $enquiry = GoldInspectionEnquiry::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'approx_grams' => $data['approx_grams'],
                'gold_type' => $data['gold_type'],
                'gold_location' => $data['gold_location'],
                'preferred_date' => $data['preferred_date'],
                'photos' => $photoPaths,
                'status' => GoldInspectionEnquiry::STATUS_NEW,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Dispatch notification email to admin
        try {
            Mail::to('deshahid9626@gmail.com')->queue(new GoldInspectionAdminNotificationMail($enquiry));
        } catch (\Exception $e) {
            Log::error("Failed to queue admin gold inspection enquiry notification mail: " . $e->getMessage());
        }

        return $enquiry;
    }

    /**
     * Update enquiry status and reviewed metadata.
     */
    public function updateStatus(GoldInspectionEnquiry $enquiry, string $status, ?string $remarks, int $userId): void
    {
        $oldStatus = $enquiry->status;
        $enquiry->status = $status;
        
        if ($remarks !== null) {
            $enquiry->admin_remark = $remarks;
        }

        $enquiry->reviewed_at = now();
        $enquiry->reviewed_by = $userId;

        $enquiry->save();

        // Write manual detailed activity log entry for the change
        $this->logDirectActivity(
            'inspection',
            $enquiry->id,
            'status_updated',
            "Gold Inspection Enquiry Status updated from '{$oldStatus}' to '{$status}'." . ($remarks ? " Remarks: {$remarks}" : "")
        );
    }

    /**
     * Fetch timeline logs (activity logs) for enquiry.
     */
    public function getTimeline(GoldInspectionEnquiry $enquiry)
    {
        return ActivityLog::where('module_name', 'inspection')
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
