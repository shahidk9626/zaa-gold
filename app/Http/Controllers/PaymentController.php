<?php

namespace App\Http\Controllers;

use App\Models\BookingPayment;
use App\Models\BookingEmiSchedule;
use App\Models\GoldBooking;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\EmiCalculationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $emiService;

    public function __construct(PaymentService $paymentService, EmiCalculationService $emiService)
    {
        $this->paymentService = $paymentService;
        $this->emiService = $emiService;
    }

    /**
     * Show EMI Payments history list
     */
    public function index(Request $request)
    {
        $query = BookingPayment::with(['booking.customer', 'emiSchedule'])->latest('payment_date');

        // 1. Filter by Search Query (Payment Number, Receipt Number, Booking Number, Customer)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', '%' . $search . '%')
                  ->orWhere('receipt_number', 'like', '%' . $search . '%')
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', '%' . $search . '%')
                         ->orWhereHas('customer', function ($cq) use ($search) {
                             $cq->where('name', 'like', '%' . $search . '%');
                         });
                  });
            });
        }

        // 2. Filter by Payment Mode
        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }

        // 3. Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 4. Filter by Date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $payments = $query->paginate(15)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    /**
     * View Details of a single payment
     */
    public function show($id)
    {
        $payment = BookingPayment::with(['booking.customer', 'booking.product', 'booking.emiPlan', 'emiSchedule'])->findOrFail($id);
        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Render the form to collect an EMI payment
     */
    public function collectForm($booking_id, $schedule_id)
    {
        $booking = GoldBooking::with(['customer', 'product', 'emiPlan'])->findOrFail($booking_id);
        $schedule = BookingEmiSchedule::where('booking_id', $booking_id)->findOrFail($schedule_id);

        if ($schedule->status === 'Paid') {
            return redirect()->route('bookings.show', $booking_id)->with('error', 'This EMI installment is already paid.');
        }

        // Calculate late fee if overdue
        $lateFee = 0.00;
        $isOverdue = false;
        $today = now();
        if ($today->startOfDay()->gt(Carbon::parse($schedule->due_date)->startOfDay())) {
            $lateFee = $this->emiService->calculateLateFee($booking->emiPlan, $schedule->emi_amount);
            $isOverdue = true;
        }

        $totalPayable = $schedule->emi_amount + $lateFee;

        return view('admin.payments.collect', compact('booking', 'schedule', 'lateFee', 'isOverdue', 'totalPayable'));
    }

    /**
     * Process and store the collected payment
     */
    public function collectStore(Request $request, $booking_id, $schedule_id)
    {
        $request->validate([
            'payment_mode' => 'required|string|in:Cash,UPI,Bank Transfer,Card,Cheque,Online Gateway',
            'transaction_reference' => 'nullable|string|max:100',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string|max:500',
        ]);

        $booking = GoldBooking::findOrFail($booking_id);
        $schedule = BookingEmiSchedule::where('booking_id', $booking_id)->findOrFail($schedule_id);

        if ($schedule->status === 'Paid') {
            return redirect()->route('bookings.show', $booking_id)->with('error', 'This EMI installment is already paid.');
        }

        try {
            $payment = $this->paymentService->collectPayment($booking, $schedule, $request->only([
                'payment_mode',
                'transaction_reference',
                'payment_date',
                'remarks'
            ]));

            return redirect()->route('bookings.show', $booking_id)->with('success', "Payment {$payment->payment_number} collected successfully! Receipt generated.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to collect payment: ' . $e->getMessage());
        }
    }

    /**
     * Export Payments list as CSV
     */
    public function exportCsv(Request $request)
    {
        $query = BookingPayment::with(['booking.customer'])->latest('payment_date');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', '%' . $search . '%')
                  ->orWhere('receipt_number', 'like', '%' . $search . '%')
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', '%' . $search . '%')
                         ->orWhereHas('customer', function ($cq) use ($search) {
                             $cq->where('name', 'like', '%' . $search . '%');
                         });
                  });
            });
        }

        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $payments = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=EMI_Payments_Report_" . now()->format('YmdHis') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Payment Number', 'Receipt Number', 'Booking Number', 'Customer Name', 'Payment Mode', 'Reference', 'Amount Paid (₹)', 'Principal Paid (₹)', 'Interest Paid (₹)', 'Late Fee Paid (₹)', 'GST Paid (₹)', 'Payment Date', 'Status'];

        $callback = function() use($payments, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->payment_number,
                    $payment->receipt_number,
                    $payment->booking->booking_number,
                    $payment->booking->customer->name ?? 'N/A',
                    $payment->payment_mode,
                    $payment->transaction_reference ?? 'N/A',
                    number_format($payment->amount_paid, 2, '.', ''),
                    number_format($payment->principal_paid, 2, '.', ''),
                    number_format($payment->interest_paid, 2, '.', ''),
                    number_format($payment->late_fee_paid, 2, '.', ''),
                    number_format($payment->gst_paid, 2, '.', ''),
                    $payment->payment_date->format('Y-m-d H:i:s'),
                    $payment->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Stubs for Edit, Update, Delete to satisfy potential RBAC
     */
    public function edit($id)
    {
        $payment = BookingPayment::findOrFail($id);
        return view('admin.payments.edit', compact('payment'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'transaction_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:500',
        ]);

        $payment = BookingPayment::findOrFail($id);
        $payment->update($request->only(['transaction_reference', 'remarks']));

        return redirect()->route('payments.show', $id)->with('success', 'Payment details updated.');
    }

    public function destroy($id)
    {
        $payment = BookingPayment::findOrFail($id);
        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }
}
