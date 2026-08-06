<?php

namespace App\Offers\Strategies;

use App\Models\Offer;
use App\Models\EmiPlan;

class FixedAmountDiscountStrategy implements OfferTypeStrategy
{
    public function calculate(Offer $offer, float $grandTotal, float $monthlyEmi, int $durationMonths): array
    {
        $fixedAmount = (float) ($offer->fixed_amount ?? 0.00);
        $savings = min($fixedAmount, $grandTotal);
        
        return [
            'savings_amount' => $savings,
            'discount_amount' => $savings,
            'final_amount' => max(0.00, round($grandTotal - $savings, 2)),
            'waived_emi_count' => 0,
        ];
    }

    public function validate(Offer $offer, EmiPlan $plan, float $grandTotal): ?string
    {
        $fixedAmount = (float) ($offer->fixed_amount ?? 0.00);
        if ($fixedAmount <= 0) {
            return 'Fixed discount amount must be greater than 0.';
        }
        if ($grandTotal > 0 && $fixedAmount > $grandTotal) {
            return 'Fixed discount amount cannot exceed the total payable amount.';
        }
        return null;
    }
}
