<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PaymentLinkService;
use App\Services\PaymentReportService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class FailedPaymentController extends Controller
{
    public function __construct(protected PaymentReportService $paymentReportService)
    {
    }

    public function index(Request $request)
    {
        $failedPayments = $this->paymentReportService->reportQuery($request->all())
            ->where('payment_status', 'Failed')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.failed', compact('failedPayments'));
    }

    public function retry(PaymentTransaction $transaction, PaymentService $paymentService, PaymentLinkService $paymentLinkService)
    {
        if ($transaction->payment_status !== 'Failed') {
            return back()->with('error', 'Only failed payments can be retried.');
        }

        try {
            if ($transaction->payment_type === 'booking') {
                $newTransaction = $paymentService->initiateBookingGatewayPayment($transaction->booking);
                return redirect()->route('payment-logs.show', $newTransaction['transaction'])->with('success', 'Booking payment retry created.');
            }

            if ($transaction->payment_type === 'emi') {
                $newTransaction = $paymentLinkService->generateLink($transaction->emiSchedule, auth()->user());
                return redirect()->route('payment-links.show', $newTransaction)->with('success', 'EMI payment retry link created.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to retry payment: ' . $e->getMessage());
        }

        return back()->with('error', 'Unsupported payment type for retry.');
    }
}
