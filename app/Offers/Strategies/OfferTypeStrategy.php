<?php

namespace App\Offers\Strategies;

use App\Models\Offer;
use App\Models\EmiPlan;

interface OfferTypeStrategy
{
    /**
     * Calculate savings / discount for this offer type
     *
     * @param Offer $offer
     * @param float $grandTotal Total Plan Value before discount
     * @param float $monthlyEmi Base monthly EMI before discount
     * @param int $durationMonths Duration of the plan
     * @return array [savings_amount, discount_amount, final_amount, waived_emi_count]
     */
    public function calculate(Offer $offer, float $grandTotal, float $monthlyEmi, int $durationMonths): array;

    /**
     * Check validation rules of this offer for a plan and grand total
     *
     * @param Offer $offer
     * @param EmiPlan $plan
     * @param float $grandTotal
     * @return string|null Error message, or null if valid
     */
    public function validate(Offer $offer, EmiPlan $plan, float $grandTotal): ?string;
}
