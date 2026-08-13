<?php

namespace App\Services;

use App\Models\BookingEmiSchedule;
use App\Models\BookingPayment;
use App\Models\GoldBooking;
use Illuminate\Support\Collection;

class FinancialCalculationService
{
    public const MONEY_SCALE = 2;
    public const OUTSTANDING_TOLERANCE = 0.50;

    public function roundMoney(float|int|string $amount): float
    {
        return round((float) $amount, self::MONEY_SCALE);
    }

    public function successfulPaymentsQuery(int $bookingId)
    {
        return BookingPayment::query()
            ->where('booking_id', $bookingId)
            ->where('status', 'Paid');
    }

    public function successfulPaymentTotal(GoldBooking|int $booking): float
    {
        $bookingId = $booking instanceof GoldBooking ? $booking->id : (int) $booking;

        return $this->roundMoney((float) $this->successfulPaymentsQuery($bookingId)->sum('amount_paid'));
    }

    public function rawOutstanding(GoldBooking $booking, ?float $paid = null): float
    {
        $paid ??= $this->successfulPaymentTotal($booking);

        return $this->roundMoney((float) $booking->grand_total - $paid);
    }

    public function normalizeOutstanding(float|int|string $outstanding): float
    {
        $amount = $this->roundMoney($outstanding);

        if (abs($amount) <= self::OUTSTANDING_TOLERANCE) {
            return 0.00;
        }

        return max(0.00, $amount);
    }

    public function outstanding(GoldBooking $booking, ?float $paid = null): float
    {
        if (in_array($booking->status, ['Cancelled', 'Refund Initiated', 'Refunded'])) {
            return 0.00;
        }

        return $this->normalizeOutstanding($this->rawOutstanding($booking, $paid));
    }

    public function displayPaidTotal(GoldBooking $booking, ?float $paid = null): float
    {
        $paid ??= $this->successfulPaymentTotal($booking);
        $rawOutstanding = $this->rawOutstanding($booking, $paid);

        if (abs($rawOutstanding) <= self::OUTSTANDING_TOLERANCE) {
            return $this->roundMoney((float) $booking->grand_total);
        }

        return min($this->roundMoney($paid), $this->roundMoney((float) $booking->grand_total));
    }

    public function remainingPayableBeforeSchedule(GoldBooking $booking, BookingEmiSchedule $schedule): float
    {
        $paidBeforeSchedule = (float) $this->successfulPaymentsQuery($booking->id)
            ->where(function ($query) use ($schedule) {
                $query->whereNull('emi_schedule_id')
                    ->orWhere('emi_schedule_id', '!=', $schedule->id);
            })
            ->sum('amount_paid');

        return $this->normalizeOutstanding($this->rawOutstanding($booking, $this->roundMoney($paidBeforeSchedule)));
    }

    public function payableBaseAmount(GoldBooking $booking, BookingEmiSchedule $schedule): float
    {
        $scheduledAmount = $this->roundMoney((float) $schedule->emi_amount);
        $remaining = $this->remainingPayableBeforeSchedule($booking, $schedule);

        if ($remaining <= 0.00) {
            return 0.00;
        }

        if ($scheduledAmount - $remaining > 0 && $scheduledAmount - $remaining <= self::OUTSTANDING_TOLERANCE) {
            return $remaining;
        }

        return min($scheduledAmount, $remaining);
    }

    public function allScheduledEmisSettled(GoldBooking $booking): bool
    {
        $total = BookingEmiSchedule::where('booking_id', $booking->id)->count();
        if ($total === 0) {
            return false;
        }

        $settled = BookingEmiSchedule::where('booking_id', $booking->id)
            ->whereIn('status', ['Paid', 'Waived'])
            ->count();

        return $settled === $total;
    }

    /**
     * Mark any remaining pending or overdue EMI schedules as Paid when booking is completed via rounding tolerance.
     */
    protected function settlePendingEmis(GoldBooking $booking): void
    {
        BookingEmiSchedule::where('booking_id', $booking->id)
            ->whereNotIn('status', ['Paid', 'Waived'])
            ->update([
                'status' => 'Paid',
                'paid_at' => now(),
                'remarks' => 'Auto-set after rounding tolerance normalization.',
            ]);
    }

    public function isComplete(GoldBooking $booking): bool
    {
        return $this->allScheduledEmisSettled($booking)
            && $this->outstanding($booking) <= 0.00;
    }

    public function completeIfEligible(GoldBooking $booking, string $remarks = 'Completed automatically after final EMI payment received.'): bool
    {
        $booking->refresh();

        // If already completed or cancelled/refunded, nothing to do
        if (in_array($booking->status, ['Completed', 'Cancelled', 'Refund Initiated', 'Refunded'])) {
            return false;
        }

        // Check if outstanding balance is within tolerance (i.e., zero after normalization)
        $outstanding = $this->outstanding($booking);
        if ($outstanding > 0) {
            return false;
        }

        // Settle any remaining pending EMI schedules as Paid
        $this->settlePendingEmis($booking);

        // Mark booking as completed
        $booking->status = 'Completed';
        $booking->status_change_remarks = $remarks;
        $booking->save();

        return true;
    }

    public function scheduleAmounts(float $grandTotal, int $duration): array
    {
        if ($duration <= 0) {
            return [];
        }

        $grandTotal = $this->roundMoney($grandTotal);
        $regularAmount = $this->roundMoney($grandTotal / $duration);
        $amounts = [];
        $runningTotal = 0.00;

        for ($i = 1; $i <= $duration; $i++) {
            if ($i === $duration) {
                $amount = $this->roundMoney($grandTotal - $runningTotal);
            } else {
                $amount = $regularAmount;
                $runningTotal = $this->roundMoney($runningTotal + $amount);
            }

            $amounts[] = $amount;
        }

        return $amounts;
    }

    public function sumScheduleAmounts(Collection|array $schedule, string $key = 'emi_amount'): float
    {
        return $this->roundMoney(collect($schedule)->sum(fn ($row) => (float) (is_array($row) ? $row[$key] : $row->{$key})));
    }
}
