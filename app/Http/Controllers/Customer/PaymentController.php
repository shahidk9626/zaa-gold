<?php

namespace App\Http\Controllers\Customer;

use App\Models\BookingPayment;
use App\Http\Controllers\ReceiptController;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class PaymentController extends CustomerBaseController
{
    public function index(): View
    {
        $payments = $this->customerService->getPaymentHistory($this->customerId());

        return view('customer.payments.index', compact('payments'));
    }

    public function downloadReceipt(int $id): BinaryFileResponse|RedirectResponse
    {
        $payment = BookingPayment::where('customer_id', $this->customerId())
            ->where('status', 'Paid')
            ->findOrFail($id);

        return app(ReceiptController::class)->downloadReceiptPdf($payment->id);
    }
}
