<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BookingDelivery;
use App\Models\BookingEmiSchedule;
use App\Models\BookingPayment;
use App\Models\GoldBooking;
use App\Models\GoldPrice;
use App\Models\GoldPriceHistory;
use App\Models\GstInvoice;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomerService
{
    public function getCustomerBookings(int $customerId, array $statuses = []): Collection
    {
        $query = GoldBooking::with(['product', 'emiPlan', 'certificate'])
            ->where('customer_id', $customerId)
            ->latest('booking_date');

        if (!empty($statuses)) {
            $query->whereIn('status', $statuses);
        }

        return $query->get()->map(fn (GoldBooking $booking) => $this->enrichBookingSummary($booking));
    }

    public function enrichBookingSummary(GoldBooking $booking): array
    {
        $schedule = BookingEmiSchedule::where('booking_id', $booking->id)->orderBy('installment_number')->get();
        $payments = BookingPayment::where('booking_id', $booking->id)->where('status', 'Paid')->get();

        $paidEmi = $schedule->where('status', 'Paid')->count();
        $totalEmi = $schedule->count() ?: (int) $booking->duration_months;
        $remainingEmi = max($totalEmi - $paidEmi, 0);
        $totalPaid = (float) $payments->sum('amount_paid');
        $outstanding = round((float) $booking->grand_total - $totalPaid, 2);
        $progress = $totalEmi > 0 ? round(($paidEmi / $totalEmi) * 100) : 0;

        return [
            'booking' => $booking,
            'schedule' => $schedule,
            'paid_emi' => $paidEmi,
            'total_emi' => $totalEmi,
            'remaining_emi' => $remainingEmi,
            'monthly_emi' => (float) $booking->monthly_emi,
            'total_paid' => $totalPaid,
            'outstanding' => $outstanding,
            'progress' => $progress,
        ];
    }

    public function getBookingDetails(int $bookingId, int $customerId): array
    {
        $booking = GoldBooking::with(['customer', 'product', 'emiPlan', 'certificate', 'statusHistory'])
            ->where('customer_id', $customerId)
            ->findOrFail($bookingId);

        $schedule = BookingEmiSchedule::where('booking_id', $booking->id)
            ->orderBy('installment_number')
            ->get();

        $payments = BookingPayment::where('booking_id', $booking->id)
            ->with('emiSchedule')
            ->latest('payment_date')
            ->get();

        $receipts = $payments->where('status', 'Paid');
        $invoices = GstInvoice::where('booking_id', $booking->id)->latest('invoice_date')->get();
        $delivery = BookingDelivery::where('booking_id', $booking->id)->latest()->first();

        $financials = $this->getFinancialSummary($booking, $receipts);

        return compact('booking', 'schedule', 'payments', 'receipts', 'invoices', 'delivery', 'financials');
    }

    public function getFinancialSummary(GoldBooking $booking, ?Collection $receipts = null): array
    {
        $receipts = $receipts ?? BookingPayment::where('booking_id', $booking->id)
            ->where('status', 'Paid')
            ->get();

        $totalBooked = (float) $booking->grand_total;
        $totalPaid = (float) $receipts->sum('amount_paid');

        return [
            'total_booked' => $totalBooked,
            'total_paid' => $totalPaid,
            'outstanding' => round($totalBooked - $totalPaid, 2),
            'principal_paid' => (float) $receipts->sum('principal_paid'),
            'interest_paid' => (float) $receipts->sum('interest_paid'),
            'late_fee_paid' => (float) $receipts->sum('late_fee_paid'),
            'gst_paid' => (float) $receipts->sum('gst_paid'),
            'gold_value' => (float) $booking->locked_gold_value,
            'finance_charge' => (float) $booking->finance_charge_amount,
            'storage_charge' => (float) $booking->storage_charge_amount,
            'gst_on_gold' => (float) $booking->gst_on_gold_amount,
            'gst_on_charges' => (float) $booking->gst_on_charges_amount,
        ];
    }

    public function getEmiHistory(int $customerId): array
    {
        $bookings = GoldBooking::where('customer_id', $customerId)->pluck('id');
        $schedule = BookingEmiSchedule::whereIn('booking_id', $bookings)
            ->with(['booking.product'])
            ->orderBy('due_date')
            ->get();

        $payments = BookingPayment::where('customer_id', $customerId)
            ->where('status', 'Paid')
            ->latest('payment_date')
            ->take(10)
            ->get();

        $paidEmi = $schedule->where('status', 'Paid')->count();
        $pendingEmi = $schedule->whereIn('status', ['Pending', 'Overdue'])->count();
        $totalPaid = (float) BookingPayment::where('customer_id', $customerId)->where('status', 'Paid')->sum('amount_paid');
        $outstanding = GoldBooking::where('customer_id', $customerId)
            ->get()
            ->sum(fn ($b) => $this->getFinancialSummary($b)['outstanding']);

        return [
            'schedule' => $schedule,
            'recent_payments' => $payments,
            'paid_emi' => $paidEmi,
            'pending_emi' => $pendingEmi,
            'remaining_emi' => $pendingEmi,
            'total_paid' => $totalPaid,
            'outstanding' => round($outstanding, 2),
        ];
    }

    public function getUpcomingEmis(int $customerId): Collection
    {
        $bookingIds = GoldBooking::where('customer_id', $customerId)
            ->whereIn('status', ['Booked', 'Active'])
            ->pluck('id');

        return BookingEmiSchedule::whereIn('booking_id', $bookingIds)
            ->whereIn('status', ['Pending', 'Overdue'])
            ->with(['booking.product'])
            ->orderBy('due_date')
            ->get();
    }

    public function getPaymentHistory(int $customerId): Collection
    {
        return BookingPayment::where('customer_id', $customerId)
            ->with(['booking.product', 'emiSchedule'])
            ->latest('payment_date')
            ->get();
    }

    public function getCustomerDeliveries(int $customerId): Collection
    {
        return BookingDelivery::with(['booking.product'])
            ->where('customer_id', $customerId)
            ->latest('request_date')
            ->get();
    }

    public function getDeliveryDetails(int $deliveryId, int $customerId): BookingDelivery
    {
        return BookingDelivery::with(['booking.product', 'statusHistories.changedBy'])
            ->where('customer_id', $customerId)
            ->findOrFail($deliveryId);
    }

    public function getCertificates(int $customerId): Collection
    {
        $bookings = GoldBooking::with(['certificate', 'product'])
            ->where('customer_id', $customerId)
            ->whereHas('certificate')
            ->get();

        $invoices = GstInvoice::where('customer_id', $customerId)
            ->where('invoice_status', '!=', 'Cancelled')
            ->latest('invoice_date')
            ->get();

        return collect([
            'price_lock_certificates' => $bookings,
            'gst_invoices' => $invoices,
        ]);
    }

    public function getLatestGoldPrice(): ?GoldPrice
    {
        return GoldPrice::where('status', 'active')->latest('effective_date')->first();
    }

    public function getGoldPriceWithTrend(): array
    {
        $latest = $this->getLatestGoldPrice();

        if (!$latest) {
            return [
                'price' => null,
                'trend_22k' => 'neutral',
                'trend_24k' => 'neutral',
            ];
        }

        $prev22k = GoldPriceHistory::where('gold_type', '22K')
            ->where('gold_price_id', '!=', $latest->id)
            ->latest('created_at')
            ->value('new_price');

        $prev24k = GoldPriceHistory::where('gold_type', '24K')
            ->where('gold_price_id', '!=', $latest->id)
            ->latest('created_at')
            ->value('new_price');

        return [
            'price' => $latest,
            'trend_22k' => $this->priceTrend((float) $latest->price_22k, (float) ($prev22k ?? $latest->price_22k)),
            'trend_24k' => $this->priceTrend((float) $latest->price_24k, (float) ($prev24k ?? $latest->price_24k)),
        ];
    }

    public function getNotifications(int $customerId): Collection
    {
        $bookingIds = GoldBooking::where('customer_id', $customerId)->pluck('id');

        return ActivityLog::where(function ($q) use ($bookingIds, $customerId) {
            $q->where(function ($inner) use ($bookingIds) {
                $inner->where('module_name', 'gold_booking')
                    ->whereIn('record_id', $bookingIds);
            })->orWhere(function ($inner) use ($customerId) {
                $inner->where('module_name', 'customer')
                    ->where('record_id', $customerId);
            });
        })
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'title' => $this->notificationTitle($log->action_type),
                'message' => $log->description,
                'type' => $log->action_type,
                'date' => $log->created_at,
            ]);
    }

    public function getRecentActivity(int $customerId, int $limit = 8): Collection
    {
        return $this->getNotifications($customerId)->take($limit);
    }

    protected function priceTrend(float $current, float $previous): string
    {
        if ($current > $previous) {
            return 'up';
        }
        if ($current < $previous) {
            return 'down';
        }

        return 'neutral';
    }

    protected function notificationTitle(string $actionType): string
    {
        return match ($actionType) {
            'payment_collected', 'emi_paid' => 'Payment Received',
            'invoice_generated' => 'Invoice Generated',
            'certificate_generated', 'certificate_downloaded' => 'Certificate Update',
            'delivery_requested', 'delivery_approved', 'delivery_dispatched', 'delivery_completed' => 'Delivery Update',
            'status_changed' => 'Booking Status Updated',
            default => 'Notification',
        };
    }
}
