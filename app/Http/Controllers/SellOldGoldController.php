<?php

namespace App\Http\Controllers;

use App\Services\SellOldGoldService;
use App\Http\Requests\StoreSellOldGoldRequest;
use App\Http\Requests\UpdateSellOldGoldRequest;
use App\Models\SellOldGoldEnquiry;
use Illuminate\Http\Request;

class SellOldGoldController extends Controller
{
    protected $oldGoldService;

    public function __construct(SellOldGoldService $oldGoldService)
    {
        $this->oldGoldService = $oldGoldService;
    }

    /**
     * Display listing of old gold enquiries
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'assigned_to', 'start_date', 'end_date']);
        $enquiries = $this->oldGoldService->getFilteredEnquiries($filters);
        $staffMembers = $this->oldGoldService->getAssigneeList();

        return view('admin.sell_old_gold.index', compact('enquiries', 'staffMembers'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $staffMembers = $this->oldGoldService->getAssigneeList();
        return view('admin.sell_old_gold.create', compact('staffMembers'));
    }

    /**
     * Store new enquiry
     */
    public function store(StoreSellOldGoldRequest $request)
    {
        $enquiry = SellOldGoldEnquiry::create($request->validated());

        $this->logDirectActivity(
            'sell_old_gold_enquiry',
            $enquiry->id,
            'enquiry_created',
            "Sell Old Gold Enquiry Created for Customer: {$enquiry->customer_name}"
        );

        return redirect()->route('sell-old-gold.index')->with('success', 'Old Gold Enquiry logged successfully.');
    }

    /**
     * Show details panel (incorporates Notes, Timeline, Status, Assignment)
     */
    public function show($id)
    {
        $enquiry = SellOldGoldEnquiry::with('assignedStaff')->findOrFail($id);
        $staffMembers = $this->oldGoldService->getAssigneeList();
        $timeline = $this->oldGoldService->getTimeline($enquiry);

        return view('admin.sell_old_gold.show', compact('enquiry', 'staffMembers', 'timeline'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $enquiry = SellOldGoldEnquiry::findOrFail($id);
        $staffMembers = $this->oldGoldService->getAssigneeList();

        return view('admin.sell_old_gold.edit', compact('enquiry', 'staffMembers'));
    }

    /**
     * Update details
     */
    public function update(UpdateSellOldGoldRequest $request, $id)
    {
        $enquiry = SellOldGoldEnquiry::findOrFail($id);
        $enquiry->update($request->validated());

        $this->logDirectActivity(
            'sell_old_gold_enquiry',
            $enquiry->id,
            'enquiry_updated',
            "Sell Old Gold Enquiry Details Updated"
        );

        return redirect()->route('sell-old-gold.show', $enquiry->id)->with('success', 'Enquiry updated successfully.');
    }

    /**
     * Change status via POST
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:New,Contacted,Inspection Scheduled,Quoted,Accepted,Rejected,Closed',
            'remarks' => 'nullable|string',
        ]);

        $enquiry = SellOldGoldEnquiry::findOrFail($id);
        $this->oldGoldService->updateStatus($enquiry, $request->status, $request->remarks);

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

        $enquiry = SellOldGoldEnquiry::findOrFail($id);
        $this->oldGoldService->assignEnquiry($enquiry, $request->assigned_to);

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

        $enquiry = SellOldGoldEnquiry::findOrFail($id);
        $this->oldGoldService->addInternalNote($enquiry, $request->note);

        return back()->with('success', 'Note added successfully.');
    }

    /**
     * Delete enquiry
     */
    public function destroy($id)
    {
        $enquiry = SellOldGoldEnquiry::findOrFail($id);
        $enquiry->delete();

        $this->logDirectActivity(
            'sell_old_gold_enquiry',
            $id,
            'enquiry_deleted',
            "Sell Old Gold Enquiry Deleted"
        );

        return redirect()->route('sell-old-gold.index')->with('success', 'Enquiry deleted successfully.');
    }

    /**
     * Export to CSV
     */
    public function exportCsv(Request $request)
    {
        $filters = $request->only(['search', 'status', 'assigned_to', 'start_date', 'end_date']);
        $query = SellOldGoldEnquiry::with('assignedStaff')->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('customer_name', 'like', '%' . $search . '%');
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
            "Content-Disposition" => "attachment; filename=Old_Gold_Enquiries_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Customer Name', 'Mobile', 'Email', 'City', 'Gold Type', 'Estimated Weight (g)', 'Estimated Value (₹)', 'Remarks', 'Status', 'Assigned To', 'Followup Date', 'Created Date'];

        $callback = function() use($enquiries, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($enquiries as $enq) {
                fputcsv($file, [
                    $enq->id,
                    $enq->customer_name,
                    $enq->mobile,
                    $enq->email,
                    $enq->city,
                    $enq->gold_type,
                    number_format($enq->estimated_weight, 2),
                    $enq->estimated_value ? number_format($enq->estimated_value, 2) : '0.00',
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
        $this->logDirectActivity('reports', 0, 'exported', 'Sell Old Gold Enquiry Report Exported to CSV');

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
