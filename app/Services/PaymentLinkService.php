<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BookingEmiSchedule;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentLinkService
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function generateLink(BookingEmiSchedule $schedule, User $generatedBy, int $expiryHours = 72): PaymentTransaction
    {
        return DB::transaction(function () use ($schedule, $generatedBy, $expiryHours) {
            $this->expireActiveLinks($schedule);

            $transaction = $this->paymentService->initiateEmiGatewayPayment(
                $schedule->loadMissing(['booking.customer', 'booking.product']),
                $generatedBy,
                'staff_link',
                now()->addHours($expiryHours)
            );

            $this->log('payment_link_generated', "Payment link generated for EMI #{$schedule->installment_number}.", $schedule->booking_id, $generatedBy->id);

            return $transaction;
        });
    }

    public function regenerateLink(PaymentTransaction $transaction, User $generatedBy): PaymentTransaction
    {
        if ($transaction->payment_type === 'booking') {
            return DB::transaction(function () use ($transaction, $generatedBy) {
                PaymentTransaction::where('booking_id', $transaction->booking_id)
                    ->where('payment_type', 'booking')
                    ->where('link_status', 'Pending')
                    ->whereIn('payment_status', ['Pending', 'Processing'])
                    ->update([
                        'link_status' => 'Expired',
                        'payment_status' => 'Expired',
                        'failure_reason' => 'Superseded by a new payment link.',
                        'updated_at' => now(),
                    ]);

                $booking = $transaction->booking;
                $newRes = $this->paymentService->initiateBookingGatewayPayment($booking);

                return $newRes['transaction'];
            });
        }

        return $this->generateLink($transaction->emiSchedule()->with('booking')->firstOrFail(), $generatedBy);
    }

    public function expireActiveLinks(BookingEmiSchedule $schedule): void
    {
        PaymentTransaction::where('emi_schedule_id', $schedule->id)
            ->where('payment_type', 'emi')
            ->where('link_status', 'Pending')
            ->whereIn('payment_status', ['Pending', 'Processing'])
            ->update([
                'link_status' => 'Expired',
                'payment_status' => 'Expired',
                'failure_reason' => 'Superseded by a new payment link.',
                'updated_at' => now(),
            ]);
    }

    public function markOpened(PaymentTransaction $transaction): PaymentTransaction
    {
        if (!$transaction->opened_at) {
            $transaction->update(['opened_at' => now()]);
            $this->log('payment_link_opened', "Payment link opened for {$transaction->transaction_number}.", $transaction->booking_id, $transaction->customer_id);
        }

        return $transaction->refresh();
    }

    public function markShared(PaymentTransaction $transaction): void
    {
        $this->log('payment_link_shared', "Payment link copied/shared for {$transaction->transaction_number}.", $transaction->booking_id, auth()->id());
    }

    protected function log(string $action, string $description, ?int $recordId, ?int $userId): void
    {
        ActivityLog::create([
            'module_name' => 'payment_link',
            'record_id' => $recordId ?: 0,
            'action_type' => $action,
            'description' => $description,
            'created_by_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
