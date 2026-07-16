<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PaymentGatewayService
{
    public function __construct(protected CashfreeService $cashfreeService)
    {
    }

    public function generatePaymentSession(PaymentTransaction $transaction, array $payload): array
    {
        return match ($transaction->gateway) {
            'cashfree' => $this->cashfreeService->createPaymentSession($transaction, $payload),
            default => throw new InvalidArgumentException("Unsupported gateway [{$transaction->gateway}]."),
        };
    }

    public function verifyGatewayResponse(PaymentTransaction $transaction): array
    {
        return match ($transaction->gateway) {
            'cashfree' => $this->cashfreeService->verifyPayment($transaction),
            default => throw new InvalidArgumentException("Unsupported gateway [{$transaction->gateway}]."),
        };
    }

    public function validateWebhook(string $gateway, Request $request): bool
    {
        return match ($gateway) {
            'cashfree' => $this->cashfreeService->validateWebhook($request),
            default => false,
        };
    }
}
