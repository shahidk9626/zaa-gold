<?php

namespace App\Http\Controllers\Customer;

use App\Models\PaymentTransaction;
use App\Services\CashfreeService;
use App\Services\PaymentGatewayService;
use App\Services\PaymentProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingPaymentController extends CustomerBaseController
{
    public function checkout(PaymentTransaction $transaction, CashfreeService $cashfreeService): View
    {
        abort_unless($transaction->customer_id === $this->customerId(), 403);
        abort_unless($transaction->payment_type === 'booking', 404);

        $session = $transaction->gateway_response ?? [];

        return view('customer.payments.cashfree-checkout', [
            'transaction' => $transaction,
            'booking' => $transaction->booking,
            'paymentSessionId' => $session['payment_session_id'] ?? null,
            'cashfreeMode' => $cashfreeService->checkoutMode(),
            'cashfreeSdkUrl' => config('services.cashfree.sdk_url'),
        ]);
    }

    public function callback(
        Request $request,
        PaymentTransaction $transaction,
        PaymentGatewayService $paymentGatewayService,
        PaymentProcessingService $paymentProcessingService
    ): RedirectResponse {
        abort_unless($transaction->customer_id === $this->customerId(), 403);
        abort_unless($transaction->payment_type === 'booking', 404);

        try {
            $verification = $paymentGatewayService->verifyGatewayResponse($transaction);
            $transaction = $paymentProcessingService->confirmBookingPayment($transaction, $verification);
        } catch (\Throwable $e) {
            return redirect()->route('customer.payments.index')
                ->with('error', 'Payment verification failed. Please contact support with transaction ' . $transaction->transaction_number . '.');
        }

        if ($transaction->isSuccessful()) {
            return redirect()->route('customer.my-plans.show', $transaction->booking_id)
                ->with('success', 'Payment verified successfully. Your booking is confirmed.');
        }

        return redirect()->route('customer.payments.index')
            ->with('error', 'Payment was not successful. Your booking has not been confirmed.');
    }
}
