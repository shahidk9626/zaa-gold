<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Models\User;
use App\Models\Product;
use App\Models\GoldBooking;
use App\Models\Role;
use App\Models\GstInvoice;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        $startDate = $request->input('start_date', \Carbon\Carbon::now('Asia/Kolkata')->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', \Carbon\Carbon::now('Asia/Kolkata')->toDateString());

        // Merge back to request so services receive defaults
        $request->merge([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $stats = $this->reportService->getDashboardStats();
        $chartData = $this->reportService->getChartData();
        $financialStats = $this->reportService->getFinancialSummaryStats($request->all());

        $reportType = $request->input('report', 'booking');
        
        $customerRole = Role::where('slug', 'customer')->first();
        $customerRoleId = $customerRole ? $customerRole->id : 0;
        
        $customers = User::where('role_id', $customerRoleId)->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $bookings = GoldBooking::orderBy('booking_number')->get();

        $query = $this->reportService->getReportQuery($reportType, $request->all());
        
        // Calculate totals for pagination footers if needed
        $totals = [];
        if (in_array($reportType, ['charge_breakdown', 'service_charge', 'gst_report', 'payment_collection'])) {
            $totals = $this->calculateQueryTotals($reportType, $query);
        }

        $reportData = $query->paginate(20)->withQueryString();

        return view('admin.reports.dashboard', compact(
            'stats',
            'chartData',
            'financialStats',
            'reportType',
            'reportData',
            'customers',
            'products',
            'bookings',
            'startDate',
            'endDate',
            'totals'
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
                $columns = ['Delivery Number', 'Booking Number', 'Customer Name', 'Delivery Method', 'Delivery Status', 'Courier', 'Tracking Number', 'Expected Delivery', 'Receiver Name', 'Receiver Mobile', 'Request Date', 'Delivered Date'];
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
                            $row->courier_partner ?? 'N/A',
                            $row->tracking_number ?? 'N/A',
                            $row->expected_delivery_date ? $row->expected_delivery_date->format('Y-m-d') : 'N/A',
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
                        $financialService = app(\App\Services\FinancialCalculationService::class);
                        $rawPaid = \App\Models\BookingPayment::where('booking_id', $row->id)->where('status', 'Paid')->sum('amount_paid');
                        $totalPaid = $financialService->displayPaidTotal($row, (float) $rawPaid);
                        $outstanding = $financialService->outstanding($row, (float) $rawPaid);
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

            case 'cancellation':
                $columns = ['Request Number', 'Booking Number', 'Customer Name', 'Customer Email', 'Gold Plan', 'Paid Amount (₹)', 'Cancellation Charge (₹)', 'Refund Amount (₹)', 'Status', 'Created Date'];
                $callback = function() use($data, $columns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, $columns);
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row->request_number,
                            $row->booking->booking_number ?? 'N/A',
                            $row->customer->name ?? 'N/A',
                            $row->customer->email ?? 'N/A',
                            $row->booking->emiPlan->plan_name ?? 'N/A',
                            $row->total_amount_paid,
                            $row->cancellation_charge_amount,
                            $row->refund_amount,
                            $row->status,
                            $row->created_at->format('Y-m-d')
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

    protected function calculateQueryTotals($reportType, $query)
    {
        $cloned = clone $query;
        switch ($reportType) {
            case 'charge_breakdown':
                $ids = $cloned->pluck('gold_bookings.id');
                $totalGoldValue = (float) $cloned->sum('locked_gold_value');
                $totalGstOnGold = (float) $cloned->sum('gst_on_gold_amount');
                $totalPriceLock = (float) $cloned->sum('finance_charge_amount');
                $totalStorage = (float) $cloned->sum('storage_charge_amount');
                $totalGstOnCharges = (float) $cloned->sum('gst_on_charges_amount');
                $totalBookingValue = (float) $cloned->sum('grand_total');

                $totalProcessingFee = (float) \DB::table('gold_bookings')
                    ->whereIn('gold_bookings.id', $ids)
                    ->join('emi_plans', 'gold_bookings.emi_plan_id', '=', 'emi_plans.id')
                    ->sum(\DB::raw('CASE WHEN emi_plans.processing_fee_type = "percent" THEN gold_bookings.locked_gold_value * (emi_plans.processing_fee / 100) ELSE emi_plans.processing_fee END'));

                $totalAmountPaid = (float) \DB::table('booking_payments')
                    ->whereIn('booking_id', $ids)
                    ->where('status', 'Paid')
                    ->sum('amount_paid');

                $totalOutstanding = max(0.00, $totalBookingValue - $totalAmountPaid);
                $totalCharges = $totalPriceLock + $totalStorage + $totalProcessingFee;

                return [
                    'gold_value' => $totalGoldValue,
                    'gst_on_gold' => $totalGstOnGold,
                    'price_lock' => $totalPriceLock,
                    'storage' => $totalStorage,
                    'insurance' => 0.00,
                    'processing_fee' => $totalProcessingFee,
                    'platform_convenience' => 0.00,
                    'delivery_charge' => 0.00,
                    'gst_on_charges' => $totalGstOnCharges,
                    'total_charges' => $totalCharges,
                    'booking_value' => $totalBookingValue,
                    'amount_paid' => $totalAmountPaid,
                    'outstanding' => $totalOutstanding,
                ];

            case 'service_charge':
                $ids = $cloned->pluck('gold_bookings.id');
                $totalStorage = (float) $cloned->sum('storage_charge_amount');
                $totalPriceLock = (float) $cloned->sum('finance_charge_amount');
                $totalGstOnCharges = (float) $cloned->sum('gst_on_charges_amount');

                $totalProcessingFee = (float) \DB::table('gold_bookings')
                    ->whereIn('gold_bookings.id', $ids)
                    ->join('emi_plans', 'gold_bookings.emi_plan_id', '=', 'emi_plans.id')
                    ->sum(\DB::raw('CASE WHEN emi_plans.processing_fee_type = "percent" THEN gold_bookings.locked_gold_value * (emi_plans.processing_fee / 100) ELSE emi_plans.processing_fee END'));

                $totalCharges = $totalPriceLock + $totalStorage + $totalProcessingFee;

                return [
                    'processing_fee' => $totalProcessingFee,
                    'platform_convenience' => 0.00,
                    'insurance' => 0.00,
                    'storage' => $totalStorage,
                    'price_lock' => $totalPriceLock,
                    'other' => 0.00,
                    'gst_on_charges' => $totalGstOnCharges,
                    'total_charges' => $totalCharges,
                ];

            case 'gst_report':
                $totalGoldValue = (float) $cloned->sum('gold_value');
                $totalGstOnGold = (float) $cloned->sum('gst_on_gold_amount');
                $totalStorage = (float) $cloned->sum('storage_charge');
                $totalPriceLock = (float) $cloned->sum('finance_charge');
                $totalGstOnCharges = (float) $cloned->sum('gst_on_charges_amount');
                $totalGst = $totalGstOnGold + $totalGstOnCharges;
                $totalInvoiceAmount = (float) $cloned->sum('grand_total');

                return [
                    'gold_value' => $totalGoldValue,
                    'gst_on_gold' => $totalGstOnGold,
                    'storage' => $totalStorage,
                    'insurance' => 0.00,
                    'price_lock' => $totalPriceLock,
                    'other_taxable' => 0.00,
                    'gst_on_charges' => $totalGstOnCharges,
                    'total_gst' => $totalGst,
                    'invoice_amount' => $totalInvoiceAmount,
                ];

            case 'payment_collection':
                $totalAmount = (float) $cloned->where('booking_payments.status', 'Paid')->sum('booking_payments.amount_paid');
                return [
                    'amount' => $totalAmount,
                ];
        }

        return [];
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', \Carbon\Carbon::now('Asia/Kolkata')->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', \Carbon\Carbon::now('Asia/Kolkata')->toDateString());

        // Merge back to request so query builders get defaults
        $request->merge([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $filters = $request->all();

        // 1. Retrieve data
        $financialStats = $this->reportService->getFinancialSummaryStats($filters);
        
        $gstReportQuery = $this->reportService->getReportQuery('gst_report', $filters);
        $gstData = $gstReportQuery->get();

        $chargeBreakdownQuery = $this->reportService->getReportQuery('charge_breakdown', $filters);
        $chargeData = $chargeBreakdownQuery->get();

        $paymentQuery = $this->reportService->getReportQuery('payment_collection', $filters);
        $paymentData = $paymentQuery->get();

        // Create Spreadsheet
        $spreadsheet = new Spreadsheet();

        // Define styling variables
        $goldHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'B4831B'],
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT],
        ];

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '78350F']],
        ];

        $metaStyle = [
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
        ];

        $totalRowStyle = [
            'font' => ['bold' => true],
            'borders' => [
                'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['rgb' => '000000']],
                'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ];

        // ----------------------------------------------------
        // SHEET 1: Financial Summary
        // ----------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Financial Summary');
        $sheet1->setShowGridlines(true);

        $sheet1->setCellValue('A1', 'AurOnGold - Financial Summary Report');
        $sheet1->getStyle('A1')->applyFromArray($titleStyle);

        $sheet1->setCellValue('A2', "Date Range: " . date('d-m-Y', strtotime($startDate)) . " to " . date('d-m-Y', strtotime($endDate)));
        $sheet1->getStyle('A2')->applyFromArray($metaStyle);

        $sheet1->setCellValue('A3', "Generated At: " . \Carbon\Carbon::now('Asia/Kolkata')->format('d M Y, h:i A') . " (Asia/Kolkata)");
        $sheet1->getStyle('A3')->applyFromArray($metaStyle);

        $sheet1->setCellValue('A5', 'Category');
        $sheet1->setCellValue('B5', 'Amount');
        $sheet1->getStyle('A5:B5')->applyFromArray($goldHeaderStyle);
        $sheet1->getStyle('B5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        $summaryMetrics = [
            'Total Gold Value' => $financialStats['total_gold_value'],
            'Total GST on Gold' => $financialStats['total_gst_on_gold'],
            'Total Service Charges' => $financialStats['total_service_charges'],
            'Total GST on Charges' => $financialStats['total_gst_on_charges'],
            'Total Storage Charges' => $financialStats['total_storage_charges'],
            'Total Insurance Charges' => $financialStats['total_insurance_charges'],
            'Total Price Lock Charges' => $financialStats['total_price_lock_charges'],
            'Total Processing Fees' => $financialStats['total_processing_fees'],
            'Total Platform Convenience Fees' => $financialStats['total_platform_convenience_fees'],
            'Total Delivery Charges' => $financialStats['total_delivery_charges'],
            'Total Payments Received' => $financialStats['total_payments_received'],
            'Total Outstanding' => $financialStats['total_outstanding'],
            'Total Refunds' => $financialStats['total_refunds'],
            'Net Collection' => $financialStats['net_collection'],
        ];

        $row = 6;
        foreach ($summaryMetrics as $label => $val) {
            $sheet1->setCellValue('A' . $row, $label);
            $sheet1->setCellValue('B' . $row, $val);
            $sheet1->getStyle('B' . $row)->getNumberFormat()->setFormatCode('[$₹-409]#,##0.00');
            
            // Highlights for Outstanding & Net Collection
            if ($label === 'Total Outstanding') {
                $sheet1->getStyle('A' . $row . ':B' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
            }
            if ($label === 'Net Collection') {
                $sheet1->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
                $sheet1->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FAFAF0');
            }
            $row++;
        }
        
        $sheet1->getColumnDimension('A')->setAutoSize(true);
        $sheet1->getColumnDimension('B')->setAutoSize(true);

        // ----------------------------------------------------
        // SHEET 2: GST Report
        // ----------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('GST Report');
        $sheet2->setShowGridlines(true);

        $sheet2->setCellValue('A1', 'AurOnGold - GST Report');
        $sheet2->getStyle('A1')->applyFromArray($titleStyle);

        $sheet2->setCellValue('A2', "Date Range: " . date('d-m-Y', strtotime($startDate)) . " to " . date('d-m-Y', strtotime($endDate)));
        $sheet2->getStyle('A2')->applyFromArray($metaStyle);

        $sheet2->setCellValue('A3', "Generated At: " . \Carbon\Carbon::now('Asia/Kolkata')->format('d M Y, h:i A') . " (Asia/Kolkata)");
        $sheet2->getStyle('A3')->applyFromArray($metaStyle);

        $gstHeaders = [
            'Invoice Number', 'Booking Number', 'Customer', 'Invoice Date', 
            'Gold Value', 'GST on Gold', 'Storage Charge', 'Insurance Charge', 
            'Price Lock Charge', 'Other Taxable Charges', 'GST on Charges', 
            'Total GST', 'Total Invoice Amount'
        ];

        $col = 'A';
        foreach ($gstHeaders as $header) {
            $sheet2->setCellValue($col . '5', $header);
            $sheet2->getStyle($col . '5')->applyFromArray($goldHeaderStyle);
            if (in_array($header, ['Gold Value', 'GST on Gold', 'Storage Charge', 'Insurance Charge', 'Price Lock Charge', 'Other Taxable Charges', 'GST on Charges', 'Total GST', 'Total Invoice Amount'])) {
                $sheet2->getStyle($col . '5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }
            $col++;
        }

        $row = 6;
        foreach ($gstData as $item) {
            $sheet2->setCellValue('A' . $row, $item->invoice_number);
            $sheet2->setCellValue('B' . $row, $item->booking->booking_number ?? 'N/A');
            $sheet2->setCellValue('C' . $row, $item->customer_name);
            $sheet2->setCellValue('D' . $row, $item->invoice_date ? $item->invoice_date->format('Y-m-d') : 'N/A');
            
            $sheet2->setCellValue('E' . $row, (float) $item->gold_value);
            $sheet2->setCellValue('F' . $row, (float) $item->gst_on_gold_amount);
            $sheet2->setCellValue('G' . $row, (float) $item->storage_charge);
            $sheet2->setCellValue('H' . $row, 0.00);
            $sheet2->setCellValue('I' . $row, (float) $item->finance_charge);
            $sheet2->setCellValue('J' . $row, 0.00);
            $sheet2->setCellValue('K' . $row, (float) $item->gst_on_charges_amount);
            $sheet2->setCellValue('L' . $row, (float) ($item->gst_on_gold_amount + $item->gst_on_charges_amount));
            $sheet2->setCellValue('M' . $row, (float) $item->grand_total);

            // Number formats
            foreach (range('E', 'M') as $c) {
                $sheet2->getStyle($c . $row)->getNumberFormat()->setFormatCode('[$₹-409]#,##0.00');
            }
            $row++;
        }

        // Totals Row for GST Report
        $sheet2->setCellValue('A' . $row, 'TOTAL');
        $sheet2->setCellValue('E' . $row, "=SUM(E6:E" . ($row - 1) . ")");
        $sheet2->setCellValue('F' . $row, "=SUM(F6:F" . ($row - 1) . ")");
        $sheet2->setCellValue('G' . $row, "=SUM(G6:G" . ($row - 1) . ")");
        $sheet2->setCellValue('H' . $row, "=SUM(H6:H" . ($row - 1) . ")");
        $sheet2->setCellValue('I' . $row, "=SUM(I6:I" . ($row - 1) . ")");
        $sheet2->setCellValue('J' . $row, "=SUM(J6:J" . ($row - 1) . ")");
        $sheet2->setCellValue('K' . $row, "=SUM(K6:K" . ($row - 1) . ")");
        $sheet2->setCellValue('L' . $row, "=SUM(L6:L" . ($row - 1) . ")");
        $sheet2->setCellValue('M' . $row, "=SUM(M6:M" . ($row - 1) . ")");

        $sheet2->getStyle('A' . $row . ':M' . $row)->applyFromArray($totalRowStyle);
        foreach (range('E', 'M') as $c) {
            $sheet2->getStyle($c . $row)->getNumberFormat()->setFormatCode('[$₹-409]#,##0.00');
        }

        // Auto column width
        foreach (range('A', 'M') as $c) {
            $sheet2->getColumnDimension($c)->setAutoSize(true);
        }

        // ----------------------------------------------------
        // SHEET 3: Charge Breakdown
        // ----------------------------------------------------
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Charge Breakdown');
        $sheet3->setShowGridlines(true);

        $sheet3->setCellValue('A1', 'AurOnGold - Charge Breakdown Report');
        $sheet3->getStyle('A1')->applyFromArray($titleStyle);

        $sheet3->setCellValue('A2', "Date Range: " . date('d-m-Y', strtotime($startDate)) . " to " . date('d-m-Y', strtotime($endDate)));
        $sheet3->getStyle('A2')->applyFromArray($metaStyle);

        $sheet3->setCellValue('A3', "Generated At: " . \Carbon\Carbon::now('Asia/Kolkata')->format('d M Y, h:i A') . " (Asia/Kolkata)");
        $sheet3->getStyle('A3')->applyFromArray($metaStyle);

        $breakdownHeaders = [
            'Booking Number', 'Customer Name', 'Customer ID', 'Booking Date', 'Product', 
            'Gold Weight (g)', 'Gold Value', 'GST on Gold', 'Price Lock Charge', 
            'Storage Charge', 'Insurance Charge', 'Processing Fee', 
            'Platform Convenience Fee', 'Delivery Charge', 'GST on Charges', 
            'Total Charges', 'Total Booking Value', 'Amount Paid', 'Outstanding', 'Payment Status'
        ];

        $col = 'A';
        foreach ($breakdownHeaders as $header) {
            $sheet3->setCellValue($col . '5', $header);
            $sheet3->getStyle($col . '5')->applyFromArray($goldHeaderStyle);
            if (!in_array($header, ['Booking Number', 'Customer Name', 'Customer ID', 'Booking Date', 'Product', 'Payment Status'])) {
                $sheet3->getStyle($col . '5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }
            $col++;
        }

        $row = 6;
        $financialService = app(\App\Services\FinancialCalculationService::class);
        $emiService = app(\App\Services\EmiCalculationService::class);

        foreach ($chargeData as $item) {
            $rowProcessingFee = $item->emiPlan ? $emiService->calculateProcessingFee($item->emiPlan, $item->locked_gold_value) : 0.00;
            $rowTotalCharges = $item->finance_charge_amount + $item->storage_charge_amount + $rowProcessingFee;
            
            $rowPaidRaw = $item->payments()->where('status', 'Paid')->sum('amount_paid');
            $rowPaid = $financialService->displayPaidTotal($item, (float) $rowPaidRaw);
            $rowOutstanding = $financialService->outstanding($item, (float) $rowPaidRaw);

            $sheet3->setCellValue('A' . $row, $item->booking_number);
            $sheet3->setCellValue('B' . $row, $item->customer->name ?? 'N/A');
            $sheet3->setCellValue('C' . $row, $item->customer_id ? 'AGCUST' . str_pad($item->customer_id, 6, '0', STR_PAD_LEFT) : 'N/A');
            $sheet3->setCellValue('D' . $row, $item->booking_date ? $item->booking_date->format('Y-m-d') : 'N/A');
            $sheet3->setCellValue('E' . $row, $item->product->name ?? 'N/A');
            
            $sheet3->setCellValue('F' . $row, (float) $item->gold_weight);
            $sheet3->setCellValue('G' . $row, (float) $item->locked_gold_value);
            $sheet3->setCellValue('H' . $row, (float) $item->gst_on_gold_amount);
            $sheet3->setCellValue('I' . $row, (float) $item->finance_charge_amount);
            $sheet3->setCellValue('J' . $row, (float) $item->storage_charge_amount);
            $sheet3->setCellValue('K' . $row, 0.00);
            $sheet3->setCellValue('L' . $row, (float) $rowProcessingFee);
            $sheet3->setCellValue('M' . $row, 0.00);
            $sheet3->setCellValue('N' . $row, 0.00);
            $sheet3->setCellValue('O' . $row, (float) $item->gst_on_charges_amount);
            $sheet3->setCellValue('P' . $row, (float) $rowTotalCharges);
            $sheet3->setCellValue('Q' . $row, (float) $item->grand_total);
            $sheet3->setCellValue('R' . $row, (float) $rowPaid);
            $sheet3->setCellValue('S' . $row, (float) $rowOutstanding);
            $sheet3->setCellValue('T' . $row, $item->status);

            $sheet3->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00"g"');
            foreach (range('G', 'S') as $c) {
                $sheet3->getStyle($c . $row)->getNumberFormat()->setFormatCode('[$₹-409]#,##0.00');
            }
            $row++;
        }

        // Totals Row for Charge Breakdown
        $sheet3->setCellValue('A' . $row, 'TOTAL');
        $sheet3->setCellValue('F' . $row, "=SUM(F6:F" . ($row - 1) . ")");
        $sheet3->setCellValue('G' . $row, "=SUM(G6:G" . ($row - 1) . ")");
        $sheet3->setCellValue('H' . $row, "=SUM(H6:H" . ($row - 1) . ")");
        $sheet3->setCellValue('I' . $row, "=SUM(I6:I" . ($row - 1) . ")");
        $sheet3->setCellValue('J' . $row, "=SUM(J6:J" . ($row - 1) . ")");
        $sheet3->setCellValue('K' . $row, "=SUM(K6:K" . ($row - 1) . ")");
        $sheet3->setCellValue('L' . $row, "=SUM(L6:L" . ($row - 1) . ")");
        $sheet3->setCellValue('M' . $row, "=SUM(M6:M" . ($row - 1) . ")");
        $sheet3->setCellValue('N' . $row, "=SUM(N6:N" . ($row - 1) . ")");
        $sheet3->setCellValue('O' . $row, "=SUM(O6:O" . ($row - 1) . ")");
        $sheet3->setCellValue('P' . $row, "=SUM(P6:P" . ($row - 1) . ")");
        $sheet3->setCellValue('Q' . $row, "=SUM(Q6:Q" . ($row - 1) . ")");
        $sheet3->setCellValue('R' . $row, "=SUM(R6:R" . ($row - 1) . ")");
        $sheet3->setCellValue('S' . $row, "=SUM(S6:S" . ($row - 1) . ")");

        $sheet3->getStyle('A' . $row . ':T' . $row)->applyFromArray($totalRowStyle);
        $sheet3->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00"g"');
        foreach (range('G', 'S') as $c) {
            $sheet3->getStyle($c . $row)->getNumberFormat()->setFormatCode('[$₹-409]#,##0.00');
        }

        // Auto column width
        foreach (range('A', 'T') as $c) {
            $sheet3->getColumnDimension($c)->setAutoSize(true);
        }

        // ----------------------------------------------------
        // SHEET 4: Payment Collection
        // ----------------------------------------------------
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Payment Collection');
        $sheet4->setShowGridlines(true);

        $sheet4->setCellValue('A1', 'AurOnGold - Payment Collection Report');
        $sheet4->getStyle('A1')->applyFromArray($titleStyle);

        $sheet4->setCellValue('A2', "Date Range: " . date('d-m-Y', strtotime($startDate)) . " to " . date('d-m-Y', strtotime($endDate)));
        $sheet4->getStyle('A2')->applyFromArray($metaStyle);

        $sheet4->setCellValue('A3', "Generated At: " . \Carbon\Carbon::now('Asia/Kolkata')->format('d M Y, h:i A') . " (Asia/Kolkata)");
        $sheet4->getStyle('A3')->applyFromArray($metaStyle);

        $paymentHeaders = [
            'Transaction Number', 'Booking Number', 'Customer', 'Payment Date', 
            'Payment Method', 'Gateway', 'Transaction ID', 'Payment Type', 
            'Amount', 'Payment Status', 'Payment Reference'
        ];

        $col = 'A';
        foreach ($paymentHeaders as $header) {
            $sheet4->setCellValue($col . '5', $header);
            $sheet4->getStyle($col . '5')->applyFromArray($goldHeaderStyle);
            if ($header === 'Amount') {
                $sheet4->getStyle($col . '5')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }
            $col++;
        }

        $row = 6;
        foreach ($paymentData as $item) {
            $paymentType = 'Other';
            if ($item->emiSchedule) {
                $paymentType = $item->emiSchedule->installment_number == 1 ? 'Initial EMAP' : 'EMI';
            } else {
                $paymentType = 'Product Purchase';
            }

            $sheet4->setCellValue('A' . $row, $item->payment_number);
            $sheet4->setCellValue('B' . $row, $item->booking->booking_number ?? 'N/A');
            $sheet4->setCellValue('C' . $row, $item->customer->name ?? 'N/A');
            $sheet4->setCellValue('D' . $row, $item->payment_date ? $item->payment_date->format('Y-m-d') : 'N/A');
            $sheet4->setCellValue('E' . $row, $item->payment_mode);
            $sheet4->setCellValue('F' . $row, $item->gateway_name ?? ($item->payment_mode === 'Online Gateway' ? 'Cashfree' : 'N/A'));
            $sheet4->setCellValue('G' . $row, $item->gateway_txn_id ?? $item->transaction_reference ?? 'N/A');
            $sheet4->setCellValue('H' . $row, $paymentType);
            $sheet4->setCellValue('I' . $row, (float) $item->amount_paid);
            $sheet4->setCellValue('J' . $row, $item->status);
            $sheet4->setCellValue('K' . $row, $item->transaction_reference ?? 'N/A');

            $sheet4->getStyle('I' . $row)->getNumberFormat()->setFormatCode('[$₹-409]#,##0.00');
            $row++;
        }

        // Totals Row for Payment Collection
        $sheet4->setCellValue('A' . $row, 'TOTAL');
        $sheet4->setCellValue('I' . $row, "=SUM(I6:I" . ($row - 1) . ")");

        $sheet4->getStyle('A' . $row . ':K' . $row)->applyFromArray($totalRowStyle);
        $sheet4->getStyle('I' . $row)->getNumberFormat()->setFormatCode('[$₹-409]#,##0.00');

        // Auto column width
        foreach (range('A', 'K') as $c) {
            $sheet4->getColumnDimension($c)->setAutoSize(true);
        }

        // Set active sheet back to Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        // Generate Excel output
        $writer = new Xlsx($spreadsheet);
        $fileName = "Financial_Reports_" . now()->format('YmdHis') . ".xlsx";

        $callback = function() use ($writer) {
            $writer->save('php://output');
        };

        // Log reports exported activity
        $this->logDirectActivity('reports', 0, 'exported', "Financial Reports exported to Excel (Date Range: {$startDate} to {$endDate})");

        return response()->stream($callback, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
