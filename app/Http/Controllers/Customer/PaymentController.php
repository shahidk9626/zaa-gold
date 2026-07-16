<?php

namespace App\Http\Controllers\Customer;

use App\Models\BookingPayment;
use App\Models\PaymentTransaction;
use App\Models\GstInvoice;
use App\Http\Controllers\ReceiptController;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class PaymentController extends CustomerBaseController
{
    public function index(): View
    {
        $payments = $this->customerService->getPaymentHistory($this->customerId());
        $transactions = PaymentTransaction::with(['booking.product', 'emiSchedule'])
            ->where('customer_id', $this->customerId())
            ->whereIn('payment_type', ['booking', 'emi'])
            ->latest()
            ->get();
        $invoices = GstInvoice::where('customer_id', $this->customerId())->get()->keyBy('payment_id');

        return view('customer.payments.index', compact('payments', 'transactions', 'invoices'));
    }

    public function downloadReceipt(int $id): Response
    {
        $payment = BookingPayment::where('customer_id', $this->customerId())
            ->where('status', 'Paid')
            ->findOrFail($id);

        return app(ReceiptController::class)->downloadReceiptPdf($payment->id);
    }
}
