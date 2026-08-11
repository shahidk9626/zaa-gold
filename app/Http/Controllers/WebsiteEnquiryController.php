<?php

namespace App\Http\Controllers;

use App\Services\WebsiteEnquiryService;
use App\Models\WebsiteEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebsiteEnquiryController extends Controller
{
    protected WebsiteEnquiryService $enquiryService;

    public function __construct(WebsiteEnquiryService $enquiryService)
    {
        $this->enquiryService = $enquiryService;
    }

    /**
     * Display a listing of the website enquiries.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'start_date', 'end_date']);
        $enquiries = $this->enquiryService->getFilteredEnquiries($filters);
        
        return view('admin.website_enquiries.index', compact('enquiries'));
    }

    /**
     * Display the specified website enquiry.
     */
    public function show($id)
    {
        $enquiry = WebsiteEnquiry::with('resolver')->findOrFail($id);
        $timeline = $this->enquiryService->getTimeline($enquiry);
        
        return view('admin.website_enquiries.show', compact('enquiry', 'timeline'));
    }

    /**
     * Update the status and internal remark of the website enquiry.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:New,In Progress,Contacted,Resolved,Closed',
            'remarks' => 'nullable|string|max:5000',
        ]);

        $enquiry = WebsiteEnquiry::findOrFail($id);
        $this->enquiryService->updateStatus($enquiry, $request->status, $request->remarks, Auth::id());

        return back()->with('success', 'Enquiry status updated successfully.');
    }

    /**
     * Remove the specified website enquiry.
     */
    public function destroy($id)
    {
        $enquiry = WebsiteEnquiry::findOrFail($id);
        $enquiry->delete();

        // Write manual activity log for deletion
        $this->logDirectActivity(
            'website_enquiry',
            $id,
            'enquiry_deleted',
            "Website Enquiry for '{$enquiry->name}' was deleted."
        );

        return redirect()->route('website-enquiries.index')->with('success', 'Enquiry deleted successfully.');
    }

    /**
     * Helper to log activity directly.
     */
    protected function logDirectActivity($module, $recordId, $action, $description)
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

        \App\Models\ActivityLog::create([
            'module_name' => $module,
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
