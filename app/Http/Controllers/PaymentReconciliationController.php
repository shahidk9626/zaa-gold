<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PaymentReconciliationService;
use App\Services\PaymentReportService;
use Illuminate\Http\Request;

class PaymentReconciliationController extends Controller
{
    public function __construct(
        protected PaymentReportService $paymentReportService,
        protected PaymentReconciliationService $paymentReconciliationService
    ) {
    }

    public function index(Request $request)
    {
        $transactions = $this->paymentReportService->reportQuery($request->all())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $reconciliation = $transactions->getCollection()
            ->mapWithKeys(fn ($transaction) => [$transaction->id => $this->paymentReconciliationService->statusFor($transaction)]);

        return view('admin.payments.reconciliation', compact('transactions', 'reconciliation'));
    }

    public function refresh(PaymentTransaction $transaction)
    {
        try {
            $this->paymentReconciliationService->refresh($transaction);
            return back()->with('success', 'Gateway status refreshed.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to refresh gateway status: ' . $e->getMessage());
        }
    }

    public function verify(PaymentTransaction $transaction)
    {
        $this->paymentReconciliationService->markVerified($transaction);

        return back()->with('success', 'Transaction marked as verified.');
    }
}
