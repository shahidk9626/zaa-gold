<?php

namespace App\Offers\Strategies;

use App\Models\Offer;
use App\Models\EmiPlan;

class EmiDiscountStrategy implements OfferTypeStrategy
{
    public function calculate(Offer $offer, float $grandTotal, float $monthlyEmi, int $durationMonths): array
    {
        $freeEmiCount = (int) ($offer->free_emi_count ?? 1);
        $savings = round($freeEmiCount * $monthlyEmi, 2);
        
        return [
            'savings_amount' => $savings,
            'discount_amount' => $savings,
            'final_amount' => max(0.00, round($grandTotal - $savings, 2)),
            'waived_emi_count' => $freeEmiCount,
        ];
    }

    public function validate(Offer $offer, EmiPlan $plan, float $grandTotal): ?string
    {
        $required = (int) ($offer->required_emi_count ?? 0);
        $free = (int) ($offer->free_emi_count ?? 0);
        $duration = (int) $plan->duration_months;

        if ($required <= 0) {
            return 'Required EMI count must be greater than 0.';
        }
        if ($free <= 0) {
            return 'Free EMI count must be greater than 0.';
        }
        if ($required >= $duration) {
            return 'Required EMI count must be smaller than the plan duration (' . $duration . ' months).';
        }
        if (($required + $free) !== $duration) {
            return 'The sum of required EMIs (' . $required . ') and free EMIs (' . $free . ') must equal the plan duration (' . $duration . ' months).';
        }
        return null;
    }
}
