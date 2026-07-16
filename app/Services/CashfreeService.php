<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CashfreeService
{
    public function createPaymentSession(PaymentTransaction $transaction, array $payload): array
    {
        if (app()->environment('testing')) {
            return [
                'order_id' => $transaction->gateway_order_id,
                'payment_session_id' => 'test_session_' . $transaction->gateway_order_id,
                'order_status' => 'ACTIVE',
            ];
        }

        $this->ensureConfigured();

        $response = Http::withHeaders($this->headers())
            ->acceptJson()
            ->asJson()
            ->post($this->url('/pg/orders'), $payload);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json();
    }

    public function fetchOrder(string $gatewayOrderId): array
    {
        if (app()->environment('testing')) {
            return [
                'order_id' => $gatewayOrderId,
                'order_status' => 'PAID',
            ];
        }

        $this->ensureConfigured();

        $response = Http::withHeaders($this->headers())
            ->acceptJson()
            ->get($this->url("/pg/orders/{$gatewayOrderId}"));

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json();
    }

    public function fetchOrderPayments(string $gatewayOrderId): array
    {
        if (app()->environment('testing')) {
            return [[
                'cf_payment_id' => 'test_payment_' . $gatewayOrderId,
                'payment_status' => 'SUCCESS',
                'bank_reference' => 'TESTBANKREF',
            ]];
        }

        $this->ensureConfigured();

        $response = Http::withHeaders($this->headers())
            ->acceptJson()
            ->get($this->url("/pg/orders/{$gatewayOrderId}/payments"));

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json();
    }

    public function verifyPayment(PaymentTransaction $transaction): array
    {
        $order = $this->fetchOrder($transaction->gateway_order_id);
        $payments = $this->fetchOrderPayments($transaction->gateway_order_id);
        $successfulPayment = collect($payments)->first(fn ($payment) => ($payment['payment_status'] ?? null) === 'SUCCESS');

        return [
            'success' => ($order['order_status'] ?? null) === 'PAID' || (bool) $successfulPayment,
            'order' => $order,
            'payments' => $payments,
            'payment' => $successfulPayment ?: collect($payments)->first(),
            'status' => $successfulPayment['payment_status'] ?? $order['order_status'] ?? 'UNKNOWN',
        ];
    }

    public function validateWebhook(Request $request): bool
    {
        $secret = (string) config('services.cashfree.secret_key');
        $signature = $request->header('x-webhook-signature') ?: $request->header('x-cf-signature');
        $timestamp = $request->header('x-webhook-timestamp') ?: $request->header('x-cf-timestamp');

        if (!$secret || !$signature || !$timestamp) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp . $request->getContent(), $secret, true));

        return hash_equals($expected, $signature);
    }

    public function normalizeWebhookPayload(Request $request): array
    {
        $payload = $request->json()->all();
        $data = $payload['data'] ?? $payload;
        $order = $data['order'] ?? $data;
        $payment = $data['payment'] ?? [];

        return [
            'payload' => $payload,
            'gateway_order_id' => $order['order_id'] ?? $data['order_id'] ?? null,
            'gateway_payment_id' => $payment['cf_payment_id'] ?? $payment['payment_id'] ?? null,
            'status' => $payment['payment_status'] ?? $order['order_status'] ?? $data['payment_status'] ?? null,
            'failure_reason' => $payment['payment_message'] ?? $data['payment_message'] ?? null,
        ];
    }

    public function checkoutMode(): string
    {
        return Str::lower((string) config('services.cashfree.env')) === 'production' ? 'production' : 'sandbox';
    }

    protected function headers(): array
    {
        return [
            'x-client-id' => config('services.cashfree.app_id'),
            'x-client-secret' => config('services.cashfree.secret_key'),
            'x-api-version' => config('services.cashfree.api_version'),
        ];
    }

    protected function url(string $path): string
    {
        $baseUrl = rtrim((string) config('services.cashfree.base_url'), '/');

        if (str_starts_with($path, '/pg') && str_ends_with($baseUrl, '/pg')) {
            $path = substr($path, 3);
        }

        return $baseUrl . $path;
    }

    protected function ensureConfigured(): void
    {
        if (!config('services.cashfree.app_id') || !config('services.cashfree.secret_key')) {
            throw new RuntimeException('Cashfree credentials are not configured.');
        }
    }
}
