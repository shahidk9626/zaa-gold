<?php

namespace App\Offers\Strategies;

use App\Models\Offer;
use App\Models\EmiPlan;

class PercentageDiscountStrategy implements OfferTypeStrategy
{
    public function calculate(Offer $offer, float $grandTotal, float $monthlyEmi, int $durationMonths): array
    {
        $percentage = (float) ($offer->percentage ?? 0.00);
        $savings = round($grandTotal * ($percentage / 100.00), 2);
        
        return [
            'savings_amount' => $savings,
            'discount_amount' => $savings,
            'final_amount' => max(0.00, round($grandTotal - $savings, 2)),
            'waived_emi_count' => 0,
        ];
    }

    public function validate(Offer $offer, EmiPlan $plan, float $grandTotal): ?string
    {
        $percentage = (float) ($offer->percentage ?? 0.00);
        if ($percentage <= 0 || $percentage > 100) {
            return 'Percentage discount must be between 0.01% and 100%.';
        }
        return null;
    }
}
