<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\CashfreeService;
use App\Services\PaymentGatewayService;
use App\Services\PaymentLinkService;
use App\Services\PaymentProcessingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewayPaymentController extends Controller
{
    public function pay(string $token, CashfreeService $cashfreeService, PaymentLinkService $paymentLinkService): View
    {
        $transaction = PaymentTransaction::with(['booking.product', 'emiSchedule', 'customer'])
            ->where('payment_token', $token)
            ->firstOrFail();

        if ($transaction->isSuccessful()) {
            return view('customer.payments.gateway-result', [
                'status' => 'success',
                'message' => 'This payment has already been completed.',
                'transaction' => $transaction,
            ]);
        }

        if ($transaction->expires_at && $transaction->expires_at->isPast()) {
            $transaction->update([
                'payment_status' => 'Expired',
                'link_status' => 'Expired',
                'failure_reason' => 'Payment link expired.',
            ]);

            return view('customer.payments.gateway-result', [
                'status' => 'expired',
                'message' => 'This payment link has expired. Please request a new payment link.',
                'transaction' => $transaction,
            ]);
        }

        if (!in_array($transaction->payment_status, ['Pending', 'Processing'], true)) {
            return view('customer.payments.gateway-result', [
                'status' => 'failed',
                'message' => 'This payment link is no longer active.',
                'transaction' => $transaction,
            ]);
        }

        $transaction = $paymentLinkService->markOpened($transaction);
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
    ): RedirectResponse|View {
        try {
            $verification = $paymentGatewayService->verifyGatewayResponse($transaction);
            $transaction = $paymentProcessingService->confirmBookingPayment($transaction, $verification);
        } catch (\Throwable $e) {
            return view('customer.payments.gateway-result', [
                'status' => 'failed',
                'message' => 'Payment verification failed. Please contact support with transaction ' . $transaction->transaction_number . '.',
                'transaction' => $transaction,
            ]);
        }

        if ($transaction->isSuccessful()) {
            if (auth()->check()) {
                if (auth()->user()->isStaffOrAdmin()) {
                    return redirect()->route('bookings.show', $transaction->booking_id)
                        ->with('success', 'Payment verified successfully.');
                }
                if (auth()->user()->isCustomer() && auth()->id() === $transaction->customer_id) {
                    return redirect()->route('customer.my-plans.show', $transaction->booking_id)
                        ->with('success', 'Payment verified successfully. Your booking is confirmed.');
                }
            }

            return view('customer.payments.gateway-result', [
                'status' => 'success',
                'message' => 'Payment verified successfully. Your booking is confirmed.',
                'transaction' => $transaction,
            ]);
        }

        return view('customer.payments.gateway-result', [
            'status' => 'failed',
            'message' => 'Payment was not successful. No EMI was updated.',
            'transaction' => $transaction,
        ]);
    }
}
