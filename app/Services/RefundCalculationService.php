<?php

namespace App\Services;

use App\Models\GoldBooking;

class RefundCalculationService
{
    /**
     * Calculate refund details for a given booking cancellation preview/submission.
     */
    public function calculateRefund(GoldBooking $booking): array
    {
        // 1. Original total plan amount locked at the time of booking.
        //    This is the full committed plan value (sum of all EMIs), NOT the
        //    current EMI, paid amount, remaining amount or outstanding balance.
        $originalPlanAmount = round((float) $booking->grand_total, 2);

        // 2. Calculate total amount paid (only successfully completed payments)
        $totalPaid = round((float) $booking->payments()
            ->where('status', 'Paid')
            ->sum('amount_paid'), 2);

        // 3. Fetch cancellation charge percentage configured on EmiPlan
        $chargePercent = (float) ($booking->emiPlan->cancellation_charge_percent ?? 0.00);

        // 4. Compute cancellation charge amount on the ORIGINAL total plan amount.
        //    Charge stays constant regardless of how many EMIs have been paid.
        $chargeAmount = round($originalPlanAmount * ($chargePercent / 100), 2);

        // 5. Compute net refund amount from what the customer actually paid,
        //    deducting the cancellation charge. Never allow a negative refund.
        $refundAmount = max(0.00, round($totalPaid - $chargeAmount, 2));

        return [
            'original_plan_amount' => $originalPlanAmount,
            'total_amount_paid' => $totalPaid,
            'cancellation_charge_percent' => $chargePercent,
            'cancellation_charge_amount' => $chargeAmount,
            'refund_amount' => $refundAmount,
        ];
    }
}
