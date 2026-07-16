<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\CashfreeService;
use App\Services\PaymentGatewayService;
use App\Services\PaymentProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashfreeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGatewayService $paymentGatewayService,
        CashfreeService $cashfreeService,
        PaymentProcessingService $paymentProcessingService
    ): JsonResponse {
        if (!$paymentGatewayService->validateWebhook('cashfree', $request)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $normalized = $cashfreeService->normalizeWebhookPayload($request);
        $transaction = PaymentTransaction::where('gateway', 'cashfree')
            ->where('gateway_order_id', $normalized['gateway_order_id'])
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        if ($transaction->webhook_processed_at && in_array($transaction->payment_status, ['Success', 'Failed', 'Cancelled'], true)) {
            return response()->json(['message' => 'Duplicate webhook ignored']);
        }

        $transaction = $paymentProcessingService->recordWebhook($transaction, $normalized['payload']);
        $verification = $paymentGatewayService->verifyGatewayResponse($transaction);
        $paymentProcessingService->confirmBookingPayment($transaction, $verification);

        return response()->json(['message' => 'Webhook processed']);
    }
}
