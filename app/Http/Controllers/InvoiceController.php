<?php

namespace App\Http\Controllers;

use App\Models\GstInvoice;
use App\Models\ActivityLog;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display GST Invoices directory
     */
    public function index(Request $request)
    {
        $query = GstInvoice::with(['booking.customer', 'booking.product'])->latest('invoice_date');

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('customer_name', 'like', '%' . $search . '%')
                  ->orWhere('customer_email', 'like', '%' . $search . '%')
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', '%' . $search . '%');
                  });
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('invoice_status', $request->status);
        }

        // Date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('admin.invoices.index', compact('invoices'));
    }

    /**
     * Show detailed invoice snapshot
     */
    public function show($id)
    {
        $invoice = GstInvoice::with(['booking.customer', 'booking.product', 'payment'])->findOrFail($id);

        // Fetch activity logs for this invoice
        $activityLogs = ActivityLog::where('module_name', 'gold_booking')
            ->where('record_id', $invoice->booking_id)
            ->whereIn('action_type', ['invoice_generated', 'invoice_downloaded', 'invoice_printed', 'invoice_cancelled'])
            ->with('user')
            ->latest()
            ->get();

        return view('admin.invoices.show', compact('invoice', 'activityLogs'));
    }

    /**
     * Download stored Invoice PDF
     */
    public function downloadPdf($id)
    {
        $invoice = GstInvoice::findOrFail($id);

        // Regenerate PDF if it doesn't exist in local disk
        if (empty($invoice->pdf_path) || !Storage::disk('public')->exists($invoice->pdf_path)) {
            $pdfPath = $this->invoiceService->generateInvoicePdf($invoice);
            $invoice->pdf_path = $pdfPath;
            $invoice->save();
        }

        // Log download activity
        $this->logActivityDirect('invoice_downloaded', "GST Invoice {$invoice->invoice_number} downloaded", $invoice->booking_id);

        return Storage::disk('public')->download($invoice->pdf_path, "Invoice_{$invoice->invoice_number}.pdf");
    }

    /**
     * Show print-friendly HTML view
     */
    public function printInvoice($id)
    {
        $invoice = GstInvoice::with(['booking.customer', 'booking.product', 'payment'])->findOrFail($id);

        // Log printed activity
        $this->logActivityDirect('invoice_printed', "GST Invoice {$invoice->invoice_number} printed", $invoice->booking_id);

        return view('admin.invoices.print', compact('invoice'));
    }

    /**
     * Cancel invoice
     */
    public function cancel($id, Request $request)
    {
        $request->validate([
            'remarks' => 'required|string|max:500'
        ]);

        $invoice = GstInvoice::findOrFail($id);
        
        if ($invoice->invoice_status === 'Cancelled') {
            return back()->with('error', 'This invoice is already cancelled.');
        }

        try {
            $this->invoiceService->cancelInvoice($invoice, $request->remarks);
            return back()->with('success', "GST Invoice {$invoice->invoice_number} has been cancelled.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel invoice: ' . $e->getMessage());
        }
    }

    /**
     * Export GST Invoices list as CSV
     */
    public function exportCsv(Request $request)
    {
        $query = GstInvoice::with(['booking.customer'])->latest('invoice_date');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('customer_name', 'like', '%' . $search . '%')
                  ->orWhere('customer_email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('invoice_status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $invoices = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=GST_Invoices_Report_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Invoice Number', 'Booking Ref', 'Customer Name', 'Customer Email', 'Product Name', 'Gold Weight (g)', 'Grand Total (₹)', 'Tax (CGST+SGST/IGST) (₹)', 'Invoice Date', 'Status'];

        $callback = function() use($invoices, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($invoices as $invoice) {
                $taxAmount = $invoice->cgst_amount + $invoice->sgst_amount + $invoice->igst_amount;
                fputcsv($file, [
                    $invoice->invoice_number,
                    $invoice->booking->booking_number,
                    $invoice->customer_name,
                    $invoice->customer_email,
                    $invoice->product_name,
                    number_format($invoice->gold_weight, 2, '.', ''),
                    number_format($invoice->grand_total, 2, '.', ''),
                    number_format($taxAmount, 2, '.', ''),
                    $invoice->invoice_date->format('Y-m-d H:i:s'),
                    $invoice->invoice_status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Direct logging inside ActivityLog schema
     */
    protected function logActivityDirect($action, $description, $recordId)
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

        ActivityLog::create([
            'module_name' => 'gold_booking',
            'record_id' => $recordId,
            'action_type' => $action,
            'old_data' => null,
            'new_data' => null,
            'description' => $description,
            'created_by_id' => Auth::id() ?? 1,
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
