<?php

namespace App\Services;

use App\Models\PaymentTransaction;

class PaymentReconciliationService
{
    public function __construct(protected PaymentGatewayService $paymentGatewayService)
    {
    }

    public function statusFor(PaymentTransaction $transaction): array
    {
        $gatewayStatus = data_get($transaction->gateway_response, 'order.order_status')
            ?? data_get($transaction->gateway_response, 'order_status')
            ?? data_get($transaction->gateway_response, 'status')
            ?? 'Unknown';

        $difference = match (true) {
            $transaction->payment_status === 'Success' && in_array($gatewayStatus, ['PAID', 'SUCCESS'], true) => 'Matched',
            in_array($transaction->payment_status, ['Pending', 'Processing'], true) => 'Pending',
            default => 'Mismatch',
        };

        return compact('gatewayStatus', 'difference');
    }

    public function refresh(PaymentTransaction $transaction): PaymentTransaction
    {
        $verification = $this->paymentGatewayService->verifyGatewayResponse($transaction);

        $transaction->update([
            'gateway_response' => $verification,
            'gateway_payment_id' => data_get($verification, 'payment.cf_payment_id', $transaction->gateway_payment_id),
            'gateway_reference' => data_get($verification, 'payment.bank_reference', $transaction->gateway_reference),
            'payment_status' => $verification['success'] ? 'Success' : $transaction->payment_status,
            'verified_at' => now(),
        ]);

        return $transaction->refresh();
    }

    public function markVerified(PaymentTransaction $transaction): PaymentTransaction
    {
        $transaction->update(['verified_at' => now()]);

        return $transaction->refresh();
    }
}
