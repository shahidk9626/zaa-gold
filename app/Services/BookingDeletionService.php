<?php

namespace App\Services;

use App\Models\BookingDelivery;
use App\Models\BookingEmiSchedule;
use App\Models\BookingPayment;
use App\Models\CancellationRequest;
use App\Models\GoldBooking;
use App\Models\GstInvoice;
use App\Models\PriceLockCertificate;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Super Admin booking soft-delete workflow.
 *
 * Soft-deleted with the booking (records owned only by this booking):
 *   booking_emi_schedules, booking_payments, gst_invoices, booking_deliveries,
 *   price_lock_certificates, cancellation_requests, referrals.
 *
 * Retained untouched (gateway, audit or shared records):
 *   payment_transactions, cash_collection_requests, booking_status_histories,
 *   delivery_status_histories, activity_logs, customer_referrals.
 */
class BookingDeletionService
{
    /**
     * Booking-owned child models, keyed by the label used in the audit summary.
     */
    protected array $ownedModels = [
        'emi_schedules' => BookingEmiSchedule::class,
        'payments' => BookingPayment::class,
        'gst_invoices' => GstInvoice::class,
        'deliveries' => BookingDelivery::class,
        'certificates' => PriceLockCertificate::class,
        'cancellation_requests' => CancellationRequest::class,
        'referrals' => Referral::class,
    ];

    public function __construct(protected AuditTrailService $auditTrailService)
    {
    }

    /**
     * Soft-delete a booking and its booking-owned records in one transaction.
     *
     * Returns the per-table soft-delete counts, or null when the booking was
     * already deleted (e.g. a repeated request).
     */
    public function delete(int $bookingId, User $actor): ?array
    {
        return DB::transaction(function () use ($bookingId, $actor) {
            $booking = GoldBooking::withTrashed()
                ->whereKey($bookingId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->trashed()) {
                return null;
            }

            $counts = [];
            foreach ($this->ownedModels as $label => $modelClass) {
                $records = $modelClass::where('booking_id', $booking->id)->get();
                // Delete per model so each record gets its own audit entry.
                $records->each->delete();
                $counts[$label] = $records->count();
            }

            $booking->delete();

            $this->auditTrailService->captureEvent(
                'gold_booking',
                $booking->id,
                'booking_deleted',
                ['booking_number' => $booking->booking_number, 'status' => $booking->status],
                ['soft_deleted_records' => $counts],
                "Booking {$booking->booking_number} (status: {$booking->status}) and its booking-owned records were soft-deleted by Super Admin {$actor->name} (User #{$actor->id}).",
                null,
                $actor->id
            );

            return $counts;
        });
    }
}
