<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Models\User;
use App\Models\Product;
use App\Models\GoldBooking;
use App\Models\Role;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display report dashboard with filters, pagination, and charts
     */
    public function index(Request $request)
    {
        $stats = $this->reportService->getDashboardStats();
        $chartData = $this->reportService->getChartData();

        $reportType = $request->input('report', 'booking');
        
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;
        
        $customers = User::where('role_id', $customerRoleId)->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $bookings = GoldBooking::orderBy('booking_number')->get();

        $query = $this->reportService->getReportQuery($reportType, $request->all());
        $reportData = $query->paginate(20)->withQueryString();

        return view('admin.reports.dashboard', compact(
            'stats',
            'chartData',
            'reportType',
            'reportData',
            'customers',
            'products',
            'bookings'
        ));
    }

    /**
     * Export specific reports to CSV
     */
    public function export(Request $request, $type)
    {
        $query = $this->reportService->getReportQuery($type, $request->all());
        $data = $query->get();

        $fileName = ucfirst(str_replace('_', ' ', $type)) . "_Report_" . now()->format('YmdHis') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=" . $fileName,
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        switch ($type) {
            case 'booking':
                $columns = ['Booking Number', 'Customer Name', 'Product Name', 'Gold Weight (g)', 'Locked Gold Price (₹/g)', 'Monthly EMI (₹)', 'Grand Total (₹)', 'Booking Date', 'Status'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->booking_number,
                            $row->customer->name ?? 'N/A',
                            $row->product->name ?? 'N/A',
                            $row->gold_weight,
                            $row->locked_price_per_gram,
                            $row->monthly_emi,
                            $row->grand_total,
                            $row->booking_date ? $row->booking_date->format('Y-m-d') : 'N/A',
                            $row->status
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'payment':
                $columns = ['Payment Number', 'Receipt Number', 'Booking Number', 'Customer Name', 'Amount Paid (₹)', 'Principal Paid (₹)', 'Interest Paid (₹)', 'Payment Mode', 'Payment Date', 'Status'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->payment_number,
                            $row->receipt_number,
                            $row->booking->booking_number ?? 'N/A',
                            $row->customer->name ?? 'N/A',
                            $row->amount_paid,
                            $row->principal_paid,
                            $row->interest_paid,
                            $row->payment_mode,
                            $row->payment_date ? $row->payment_date->format('Y-m-d') : 'N/A',
                            $row->status
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'customer':
                $columns = ['Customer Name', 'Email', 'Phone', 'WhatsApp Number', 'Status', 'Registered Date'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->name,
                            $row->email,
                            $row->phone ?? 'N/A',
                            $row->whatsapp_number ?? 'N/A',
                            $row->status,
                            $row->created_at->format('Y-m-d')
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'product':
                $columns = ['Product Name', 'SKU', 'Weight (g)', 'Purity (%)', 'Gold Type', 'Status'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->name,
                            $row->sku,
                            $row->weight_in_grams,
                            $row->purity,
                            $row->gold_type,
                            $row->status
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'delivery':
                $columns = ['Delivery Number', 'Booking Number', 'Customer Name', 'Delivery Method', 'Delivery Status', 'Receiver Name', 'Receiver Mobile', 'Request Date', 'Delivered Date'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->delivery_number,
                            $row->booking->booking_number ?? 'N/A',
                            $row->customer->name ?? 'N/A',
                            $row->delivery_method,
                            $row->delivery_status,
                            $row->receiver_name ?? 'N/A',
                            $row->receiver_mobile ?? 'N/A',
                            $row->request_date ? $row->request_date->format('Y-m-d') : 'N/A',
                            $row->delivered_date ? $row->delivered_date->format('Y-m-d') : 'N/A'
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'emi':
                $columns = ['Booking Number', 'Customer Name', 'Installment #', 'EMI Amount (₹)', 'Due Date', 'Status', 'Paid Date'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->booking->booking_number ?? 'N/A',
                            $row->booking->customer->name ?? 'N/A',
                            $row->installment_number,
                            $row->emi_amount,
                            $row->due_date,
                            $row->status,
                            $row->paid_at ? $row->paid_at->format('Y-m-d') : 'N/A'
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'outstanding':
                $columns = ['Booking Number', 'Customer Name', 'Product Name', 'Grand Total (₹)', 'Total Paid (₹)', 'Outstanding Balance (₹)', 'Status'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        $totalPaid = \App\Models\BookingPayment::where('booking_id', $row->id)->where('status', 'Paid')->sum('amount_paid');
                        $outstanding = max($row->grand_total - $totalPaid, 0);
                        fputcsv($file, [
                            $row->booking_number,
                            $row->customer->name ?? 'N/A',
                            $row->product->name ?? 'N/A',
                            $row->grand_total,
                            $totalPaid,
                            $outstanding,
                            $row->status
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'referral':
                $columns = ['Referral Code', 'Referrer Customer', 'Referred Customer', 'Booking Number', 'Reward Type', 'Reward Amount (₹)', 'Status', 'Created Date'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->referral_code,
                            $row->referrer->name ?? 'N/A',
                            $row->referred->name ?? 'N/A',
                            $row->booking->booking_number ?? 'N/A',
                            $row->reward_type,
                            $row->reward_amount,
                            $row->reward_status,
                            $row->created_at->format('Y-m-d')
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'sell_old_gold':
                $columns = ['Customer Name', 'Mobile', 'Email', 'City', 'Gold Type', 'Estimated Weight (g)', 'Estimated Value (₹)', 'Assigned Staff', 'Status', 'Created Date'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->customer_name,
                            $row->mobile,
                            $row->email ?? 'N/A',
                            $row->city ?? 'N/A',
                            $row->gold_type,
                            $row->estimated_weight,
                            $row->estimated_value ?? '0.00',
                            $row->assignedStaff->name ?? 'Unassigned',
                            $row->status,
                            $row->created_at->format('Y-m-d')
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'franchise':
                $columns = ['Full Name', 'Mobile', 'Email', 'City', 'State', 'Budget (₹)', 'Current Business', 'Assigned Staff', 'Status', 'Created Date'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->full_name,
                            $row->mobile,
                            $row->email,
                            $row->city,
                            $row->state,
                            $row->investment_budget,
                            $row->current_business ?? 'N/A',
                            $row->assignedStaff->name ?? 'Unassigned',
                            $row->status,
                            $row->created_at->format('Y-m-d')
                        ]);
                    }
                    fclose($file);
                };
                break;

            case 'purchase_limit':
                $columns = ['Customer Name', 'Allowed Limit (g)', 'Purchased (g)', 'Remaining (g)', 'Exceeded'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    $maxLimit = (float) \App\Models\SystemSetting::get('customer_max_purchase_grams', 100.00);
                    foreach ($data as $row) {
                        $purchased = (float) $row->purchased_weight;
                        $remaining = max(0.00, $maxLimit - $purchased);
                        $exceeded = $purchased > $maxLimit ? 'Yes (' . ($purchased - $maxLimit) . 'g)' : 'No';
                        fputcsv($file, [
                            $row->name,
                            $maxLimit,
                            $purchased,
                            $remaining,
                            $exceeded
                        ]);
                    }
                    fclose($file);
                };
                break;

            default:
                return back()->with('error', 'Invalid report type for export.');
        }

        // Log reports exported activity
        $this->logDirectActivity('reports', 0, 'exported', "Reports of type {$type} exported to CSV");

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
