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
        // 1. Calculate total amount paid (only successfully completed payments)
        $totalPaid = (float) $booking->payments()
            ->where('status', 'Paid')
            ->sum('amount_paid');

        // 2. Fetch cancellation charge percentage configured on EmiPlan
        $chargePercent = (float) ($booking->emiPlan->cancellation_charge_percent ?? 0.00);

        // 3. Compute cancellation charge amount
        $chargeAmount = round($totalPaid * ($chargePercent / 100), 2);

        // 4. Compute net refund amount
        $refundAmount = max(0.00, round($totalPaid - $chargeAmount, 2));

        return [
            'total_amount_paid' => $totalPaid,
            'cancellation_charge_percent' => $chargePercent,
            'cancellation_charge_amount' => $chargeAmount,
            'refund_amount' => $refundAmount,
        ];
    }
}
