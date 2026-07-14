<?php

namespace App\Http\Controllers;

use App\Services\FranchiseService;
use App\Http\Requests\StoreFranchiseRequest;
use App\Http\Requests\UpdateFranchiseRequest;
use App\Models\FranchiseEnquiry;
use Illuminate\Http\Request;

class FranchiseController extends Controller
{
    protected $franchiseService;

    public function __construct(FranchiseService $franchiseService)
    {
        $this->franchiseService = $franchiseService;
    }

    /**
     * Display listing of franchise enquiries
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'assigned_to', 'start_date', 'end_date']);
        $enquiries = $this->franchiseService->getFilteredEnquiries($filters);
        $staffMembers = $this->franchiseService->getAssigneeList();

        return view('admin.franchise.index', compact('enquiries', 'staffMembers'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $staffMembers = $this->franchiseService->getAssigneeList();
        return view('admin.franchise.create', compact('staffMembers'));
    }

    /**
     * Store new enquiry
     */
    public function store(StoreFranchiseRequest $request)
    {
        $enquiry = FranchiseEnquiry::create($request->validated());

        $this->logDirectActivity(
            'franchise_enquiry',
            $enquiry->id,
            'enquiry_created',
            "Franchise Enquiry Created for Customer: {$enquiry->full_name}"
        );

        return redirect()->route('franchise.index')->with('success', 'Franchise Enquiry logged successfully.');
    }

    /**
     * Show details panel (incorporates Notes, Timeline, Status, Assignment)
     */
    public function show($id)
    {
        $enquiry = FranchiseEnquiry::with('assignedStaff')->findOrFail($id);
        $staffMembers = $this->franchiseService->getAssigneeList();
        $timeline = $this->franchiseService->getTimeline($enquiry);

        return view('admin.franchise.show', compact('enquiry', 'staffMembers', 'timeline'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $enquiry = FranchiseEnquiry::findOrFail($id);
        $staffMembers = $this->franchiseService->getAssigneeList();

        return view('admin.franchise.edit', compact('enquiry', 'staffMembers'));
    }

    /**
     * Update details
     */
    public function update(UpdateFranchiseRequest $request, $id)
    {
        $enquiry = FranchiseEnquiry::findOrFail($id);
        $enquiry->update($request->validated());

        $this->logDirectActivity(
            'franchise_enquiry',
            $enquiry->id,
            'enquiry_updated',
            "Franchise Enquiry Details Updated"
        );

        return redirect()->route('franchise.show', $enquiry->id)->with('success', 'Enquiry updated successfully.');
    }

    /**
     * Change status via POST
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:New,Contacted,Meeting Scheduled,Proposal Sent,Approved,Rejected,Closed',
            'remarks' => 'nullable|string',
        ]);

        $enquiry = FranchiseEnquiry::findOrFail($id);
        $this->franchiseService->updateStatus($enquiry, $request->status, $request->remarks);

        return back()->with('success', 'Status updated successfully.');
    }

    /**
     * Assign staff member
     */
    public function assignStaff(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $enquiry = FranchiseEnquiry::findOrFail($id);
        $this->franchiseService->assignEnquiry($enquiry, $request->assigned_to);

        return back()->with('success', 'Assignee updated successfully.');
    }

    /**
     * Add timeline note
     */
    public function addNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string',
        ]);

        $enquiry = FranchiseEnquiry::findOrFail($id);
        $this->franchiseService->addInternalNote($enquiry, $request->note);

        return back()->with('success', 'Note added successfully.');
    }

    /**
     * Delete enquiry
     */
    public function destroy($id)
    {
        $enquiry = FranchiseEnquiry::findOrFail($id);
        $enquiry->delete();

        $this->logDirectActivity(
            'franchise_enquiry',
            $id,
            'enquiry_deleted',
            "Franchise Enquiry Deleted"
        );

        return redirect()->route('franchise.index')->with('success', 'Enquiry deleted successfully.');
    }

    /**
     * Export to CSV
     */
    public function exportCsv(Request $request)
    {
        $filters = $request->only(['search', 'status', 'assigned_to', 'start_date', 'end_date']);
        $query = FranchiseEnquiry::with('assignedStaff')->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('full_name', 'like', '%' . $search . '%');
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        $enquiries = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Franchise_Enquiries_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Full Name', 'Mobile', 'Email', 'City', 'State', 'Investment Budget', 'Business Experience', 'Current Business', 'Message', 'Remarks', 'Status', 'Assigned To', 'Followup Date', 'Created Date'];

        $callback = function() use($enquiries, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($enquiries as $enq) {
                fputcsv($file, [
                    $enq->id,
                    $enq->full_name,
                    $enq->mobile,
                    $enq->email,
                    $enq->city,
                    $enq->state,
                    $enq->investment_budget,
                    $enq->business_experience,
                    $enq->current_business,
                    $enq->message,
                    $enq->remarks,
                    $enq->status,
                    $enq->assignedStaff->name ?? 'Unassigned',
                    $enq->followup_date ? $enq->followup_date->format('Y-m-d H:i:s') : 'N/A',
                    $enq->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        // Log reports exported
        $this->logDirectActivity('reports', 0, 'exported', 'Franchise Enquiry Report Exported to CSV');

        return response()->stream($callback, 200, $headers);
    }

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
            'created_by_id' => auth()->id() ?? 1,
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
