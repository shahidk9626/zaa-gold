<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\GoldBooking;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class PaymentProcessingService
{
    public function __construct(
        protected BookingService $bookingService,
        protected PaymentService $paymentService,
        protected InvoiceService $invoiceService
    ) {
    }

    public function confirmBookingPayment(PaymentTransaction $transaction, array $verification): PaymentTransaction
    {
        if ($transaction->payment_type === 'emi') {
            return $this->confirmEmiPayment($transaction, $verification);
        }

        return DB::transaction(function () use ($transaction, $verification) {
            $transaction = PaymentTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->isSuccessful()) {
                return $transaction;
            }

            $booking = GoldBooking::query()
                ->whereKey($transaction->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$verification['success']) {
                $this->markFailed($transaction, $verification);
                return $transaction;
            }

            $payment = $verification['payment'] ?? [];
            $transaction->update([
                'gateway_payment_id' => $payment['cf_payment_id'] ?? $payment['payment_id'] ?? $transaction->gateway_payment_id,
                'gateway_reference' => $payment['bank_reference'] ?? $payment['auth_id'] ?? $transaction->gateway_reference,
                'payment_status' => 'Success',
                'gateway_response' => $verification,
                'failure_reason' => null,
                'verified_at' => now(),
                'paid_at' => now(),
                'updated_by_id' => auth()->id(),
            ]);

            if (str_starts_with((string) $booking->booking_number, 'DRAFT-')) {
                $booking->booking_number = $this->bookingService->generateBookingNumber();
            }

            if ($booking->status === 'Draft') {
                $booking->status = 'Booked';
                $booking->booking_date = now();
                $booking->status_change_remarks = 'Confirmed after Cashfree payment verification.';
                $booking->save();
            }

            if (!$booking->certificate()->exists()) {
                $this->bookingService->generateCertificate($booking);
            }

            if (!\App\Models\BookingEmiSchedule::where('booking_id', $booking->id)->exists()) {
                $this->paymentService->generateScheduleForBooking($booking);
            }

            if (!\App\Models\BookingPayment::where('booking_id', $booking->id)->where('status', 'Paid')->exists()) {
                $this->paymentService->processFirstEmiPayment($booking, [
                    'payment_mode' => 'Online Gateway',
                    'transaction_reference' => $transaction->gateway_payment_id ?: $transaction->gateway_order_id,
                    'remarks' => "First EMI paid through {$transaction->gateway} transaction {$transaction->transaction_number}.",
                    'payment_date' => $transaction->paid_at ?: now(),
                ]);
            }

            if (!\App\Models\GstInvoice::where('booking_id', $booking->id)->exists()) {
                $receipt = \App\Models\BookingPayment::where('booking_id', $booking->id)
                    ->where('status', 'Paid')
                    ->latest('payment_date')
                    ->first();

                if ($receipt) {
                    $this->invoiceService->generateInvoice($receipt);
                }
            }

            $this->log('booking_payment_success', "Payment {$transaction->transaction_number} verified and booking {$booking->booking_number} confirmed.", $booking->id, $transaction->customer_id);
            $this->log('booking_confirmed', "Booking {$booking->booking_number} confirmed after successful payment.", $booking->id, $transaction->customer_id);

            return $transaction->refresh();
        });
    }

    public function confirmEmiPayment(PaymentTransaction $transaction, array $verification): PaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $verification) {
            $transaction = PaymentTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->isSuccessful()) {
                return $transaction;
            }

            $schedule = \App\Models\BookingEmiSchedule::query()
                ->whereKey($transaction->emi_schedule_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($schedule->status === 'Paid') {
                $transaction->update([
                    'payment_status' => 'Cancelled',
                    'link_status' => 'Cancelled',
                    'failure_reason' => 'EMI installment was already paid.',
                    'verified_at' => now(),
                ]);

                return $transaction->refresh();
            }

            if (!$verification['success']) {
                $this->markFailed($transaction, $verification);
                return $transaction->refresh();
            }

            $booking = $schedule->booking()->lockForUpdate()->firstOrFail();
            $payment = $verification['payment'] ?? [];

            $transaction->update([
                'gateway_payment_id' => $payment['cf_payment_id'] ?? $payment['payment_id'] ?? $transaction->gateway_payment_id,
                'gateway_reference' => $payment['bank_reference'] ?? $payment['auth_id'] ?? $transaction->gateway_reference,
                'payment_status' => 'Success',
                'link_status' => 'Paid',
                'gateway_response' => $verification,
                'failure_reason' => null,
                'verified_at' => now(),
                'paid_at' => now(),
                'updated_by_id' => auth()->id(),
            ]);

            $receipt = \App\Models\BookingPayment::where('booking_id', $booking->id)
                ->where('emi_schedule_id', $schedule->id)
                ->where('status', 'Paid')
                ->first();

            if (!$receipt) {
                $receipt = $this->paymentService->collectPayment($booking, $schedule, [
                    'payment_mode' => 'Online Gateway',
                    'transaction_reference' => $transaction->gateway_payment_id ?: $transaction->gateway_order_id,
                    'remarks' => "EMI paid through {$transaction->gateway} transaction {$transaction->transaction_number}.",
                    'payment_date' => $transaction->paid_at ?: now(),
                ]);
            }

            if (!\App\Models\GstInvoice::where('payment_id', $receipt->id)->exists()) {
                $this->invoiceService->generateInvoice($receipt);
            }

            $this->log('emi_payment_success', "EMI #{$schedule->installment_number} payment {$transaction->transaction_number} verified.", $booking->id, $transaction->customer_id);
            $this->log('emi_paid', "EMI #{$schedule->installment_number} marked paid for booking {$booking->booking_number}.", $booking->id, $transaction->customer_id);
            $this->log('outstanding_updated', "Outstanding updated after EMI #{$schedule->installment_number} payment.", $booking->id, $transaction->customer_id);

            return $transaction->refresh();
        });
    }

    public function markFailed(PaymentTransaction $transaction, array $verification): PaymentTransaction
    {
        $transaction->update([
            'payment_status' => 'Failed',
            'link_status' => $transaction->payment_type === 'emi' ? 'Failed' : $transaction->link_status,
            'gateway_response' => $verification,
            'failure_reason' => $verification['status'] ?? 'Payment verification failed',
            'verified_at' => now(),
            'updated_by_id' => auth()->id(),
        ]);

        $this->log('booking_payment_failed', "Payment {$transaction->transaction_number} failed gateway verification.", $transaction->booking_id, $transaction->customer_id);

        return $transaction->refresh();
    }

    public function recordWebhook(PaymentTransaction $transaction, array $payload): PaymentTransaction
    {
        if ($transaction->webhook_processed_at && in_array($transaction->payment_status, ['Success', 'Failed', 'Cancelled'], true)) {
            return $transaction;
        }

        $transaction->update([
            'webhook_payload' => $payload,
            'webhook_processed_at' => now(),
        ]);

        $this->log('cashfree_webhook_received', "Cashfree webhook received for {$transaction->transaction_number}.", $transaction->booking_id, $transaction->customer_id);

        return $transaction->refresh();
    }

    protected function log(string $action, string $description, ?int $recordId, ?int $userId): void
    {
        ActivityLog::create([
            'module_name' => 'gold_booking',
            'record_id' => $recordId ?: 0,
            'action_type' => $action,
            'description' => $description,
            'created_by_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
