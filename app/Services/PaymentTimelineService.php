<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\PaymentTransaction;
use Illuminate\Support\Collection;

class PaymentTimelineService
{
    public function forTransaction(PaymentTransaction $transaction): Collection
    {
        $events = collect([
            $this->event('Payment Created', $transaction->created_at, 'Transaction created.'),
            $this->event('Payment Session Generated', $transaction->gateway_response ? $transaction->updated_at : null, 'Gateway session generated.'),
            $this->event('Customer Redirected', $transaction->opened_at, 'Customer opened checkout/payment link.'),
            $this->event('Webhook Received', $transaction->webhook_processed_at, 'Webhook received from gateway.'),
            $this->event('Payment Verified', $transaction->verified_at, 'Gateway status verified.'),
            $this->event('Completed', $transaction->paid_at, 'Payment completed successfully.'),
        ])->filter(fn ($event) => $event['at']);

        $activity = ActivityLog::where(function ($q) use ($transaction) {
            $q->where('description', 'like', '%' . $transaction->transaction_number . '%')
                ->orWhere('record_id', $transaction->booking_id);
        })->latest()->get()->map(fn ($log) => [
            'label' => \Illuminate\Support\Str::headline($log->action_type),
            'at' => $log->created_at,
            'description' => $log->description,
        ]);

        return $events->merge($activity)->sortBy('at')->values();
    }

    protected function event(string $label, $at, string $description): array
    {
        return compact('label', 'at', 'description');
    }
}
