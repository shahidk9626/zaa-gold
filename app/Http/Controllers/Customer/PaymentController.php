<?php

namespace App\Http\Controllers\Customer;

use App\Models\BookingPayment;
use App\Models\PaymentTransaction;
use App\Models\GstInvoice;
use App\Models\GoldBooking;
use App\Http\Controllers\ReceiptController;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;
use Illuminate\Http\Request;

class PaymentController extends CustomerBaseController
{
    public function index(Request $request): View
    {
        $customerId = $this->customerId();

        // 1. Get customer's authorized bookings for the filter dropdown
        $bookings = GoldBooking::where('customer_id', $customerId)
            ->whereNotNull('booking_number')
            ->orderBy('booking_number')
            ->get();

        // 2. Query transactions
        $query = PaymentTransaction::with(['booking.product', 'emiSchedule'])
            ->where('customer_id', $customerId)
            ->whereIn('payment_type', ['booking', 'emi']);

        // Filter by booking_id
        if ($request->filled('booking_id')) {
            $bookingId = $request->input('booking_id');
            $query->where('booking_id', $bookingId);
        }

        // Filter by date range (start_date and end_date in Y-m-d)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('start_date'), 'Asia/Kolkata')->startOfDay()->setTimezone('UTC');
            $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('end_date'), 'Asia/Kolkata')->endOfDay()->setTimezone('UTC');
            
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $transactions = $query->latest('created_at')->get();

        // 3. Load other payments & invoices for O(1) in-memory lookup
        $invoices = GstInvoice::where('customer_id', $customerId)->get()->keyBy('payment_id');
        $payments = BookingPayment::where('customer_id', $customerId)->get();

        $paymentsByBooking = $payments->whereNull('emi_schedule_id')->keyBy('booking_id');
        $paymentsByEmi = $payments->whereNotNull('emi_schedule_id')->keyBy('emi_schedule_id');

        return view('customer.payments.index', compact('transactions', 'invoices', 'bookings', 'paymentsByBooking', 'paymentsByEmi'));
    }

    public function downloadReceipt(int $id): Response
    {
        $payment = BookingPayment::where('customer_id', $this->customerId())
            ->where('status', 'Paid')
            ->findOrFail($id);

        return app(ReceiptController::class)->downloadReceiptPdf($payment->id);
    }
}
