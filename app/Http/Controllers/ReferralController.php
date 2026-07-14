<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use App\Http\Requests\StoreReferralRequest;
use App\Http\Requests\UpdateReferralRequest;
use App\Models\Referral;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    protected $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Display listing of referrals
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'reward_type', 'start_date', 'end_date']);
        $referrals = $this->referralService->getFilteredReferrals($filters);

        return view('admin.referrals.index', compact('referrals'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $customers = $this->referralService->getCustomers();
        return view('admin.referrals.create', compact('customers'));
    }

    /**
     * Store new referral
     */
    public function store(StoreReferralRequest $request)
    {
        $referral = $this->referralService->createReferral($request->validated());

        // Log activity
        $this->logDirectActivity(
            'referral',
            $referral->id,
            'referral_created',
            "Referral created: Code {$referral->referral_code} from Referrer ID {$referral->referrer_customer_id} to Referred ID {$referral->referred_customer_id}"
        );

        return redirect()->route('referrals.index')->with('success', 'Referral program entry created successfully.');
    }

    /**
     * Show referral details
     */
    public function show($id)
    {
        $referral = Referral::with(['referrer', 'referred', 'booking.product'])->findOrFail($id);
        
        $activityLogs = ActivityLog::where('module_name', 'referral')
            ->where('record_id', $referral->id)
            ->with('user')
            ->latest()
            ->get();

        return view('admin.referrals.show', compact('referral', 'activityLogs'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $referral = Referral::findOrFail($id);
        $customers = $this->referralService->getCustomers();
        $bookings = $this->referralService->getCustomerBookings($referral->referred_customer_id);

        return view('admin.referrals.edit', compact('referral', 'customers', 'bookings'));
    }

    /**
     * Update referral
     */
    public function update(UpdateReferralRequest $request, $id)
    {
        $referral = Referral::findOrFail($id);
        $oldStatus = $referral->reward_status;

        $this->referralService->updateReferral($referral, $request->validated());

        // Log status update activity if status changed
        if ($oldStatus !== $referral->reward_status) {
            $this->logDirectActivity(
                'referral',
                $referral->id,
                'referral_updated',
                "Referral status updated from {$oldStatus} to {$referral->reward_status} for Code {$referral->referral_code}"
            );
        } else {
            $this->logDirectActivity(
                'referral',
                $referral->id,
                'referral_updated',
                "Referral details updated for Code {$referral->referral_code}"
            );
        }

        return redirect()->route('referrals.show', $referral->id)->with('success', 'Referral program entry updated successfully.');
    }

    /**
     * Export filtered list to CSV
     */
    public function exportCsv(Request $request)
    {
        $filters = $request->only(['search', 'status', 'reward_type', 'start_date', 'end_date']);
        $referrals = Referral::with(['referrer', 'referred', 'booking'])
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('referral_code', 'like', '%' . $search . '%');
            })
            ->when(!empty($filters['status']), function ($q) use ($filters) {
                $q->where('reward_status', $filters['status']);
            })
            ->when(!empty($filters['reward_type']), function ($q) use ($filters) {
                $q->where('reward_type', $filters['reward_type']);
            })
            ->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Referral_Report_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Referral Code', 'Referrer Customer', 'Referred Customer', 'Booking Number', 'Reward Type', 'Reward Amount', 'Reward Status', 'Remarks', 'Created Date'];

        $callback = function() use($referrals, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($referrals as $ref) {
                fputcsv($file, [
                    $ref->referral_code,
                    $ref->referrer->name ?? 'N/A',
                    $ref->referred->name ?? 'N/A',
                    $ref->booking->booking_number ?? 'N/A',
                    $ref->reward_type,
                    number_format($ref->reward_amount, 2),
                    $ref->reward_status,
                    $ref->remarks,
                    $ref->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        // Log reports exported
        $this->logDirectActivity('reports', 0, 'exported', 'Referrals Report Exported to CSV');

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
