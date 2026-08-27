<?php

namespace App\Http\Controllers;

use App\Services\GoldInspectionEnquiryService;
use App\Models\GoldInspectionEnquiry;
use Illuminate\Http\Request;

class GoldInspectionEnquiryController extends Controller
{
    protected $inspectionService;

    public function __construct(GoldInspectionEnquiryService $inspectionService)
    {
        $this->inspectionService = $inspectionService;
    }

    /**
     * Display listing of inspection enquiries.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'start_date', 'end_date']);
        $enquiries = $this->inspectionService->getFilteredEnquiries($filters);

        return view('admin.inspections.index', compact('enquiries'));
    }

    /**
     * Show details of a specific inspection enquiry.
     */
    public function show($id)
    {
        $enquiry = GoldInspectionEnquiry::with(['reviewer'])->findOrFail($id);
        $timeline = $this->inspectionService->getTimeline($enquiry);

        return view('admin.inspections.show', compact('enquiry', 'timeline'));
    }

    /**
     * Update status and admin remarks of an inspection enquiry.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', GoldInspectionEnquiry::getStatuses()),
            'remarks' => 'nullable|string|max:1000',
        ]);

        $enquiry = GoldInspectionEnquiry::findOrFail($id);
        $this->inspectionService->updateStatus($enquiry, $request->status, $request->remarks, auth()->id());

        return redirect()->route('inspections.show', $enquiry->id)->with('success', 'Status and remarks updated successfully.');
    }

    /**
     * Soft delete an inspection enquiry.
     */
    public function destroy($id)
    {
        $enquiry = GoldInspectionEnquiry::findOrFail($id);
        $enquiry->delete();

        // Direct activity logging
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
            'module_name' => 'inspection',
            'record_id' => $id,
            'action_type' => 'enquiry_deleted',
            'description' => "Gold Inspection Enquiry Deleted. ID: {$id}",
            'created_by_id' => auth()->id() ?? 1,
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);

        return redirect()->route('inspections.index')->with('success', 'Gold Inspection Enquiry deleted successfully.');
    }
}
