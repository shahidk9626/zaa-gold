<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\EmiPlan;
use App\Models\User;
use App\Models\Product;

class OfferEligibilityService
{
    protected OfferCalculationService $calculationService;

    public function __construct(OfferCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Get all active eligible offers mapped to a plan
     *
     * @param EmiPlan $plan
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEligibleOffersForPlan(EmiPlan $plan)
    {
        return $plan->offers()
            ->active()
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Check if a specific offer is eligible for a plan
     *
     * @param Offer $offer
     * @param EmiPlan $plan
     * @param User|null $customer
     * @param Product|null $product
     * @return bool
     */
    public function isOfferEligible(Offer $offer, EmiPlan $plan, ?User $customer = null, ?Product $product = null): bool
    {
        // 1. Basic Active state check
        $now = now();
        if ($offer->status !== 'Active') {
            return false;
        }

        if ($offer->start_date && $offer->start_date->gt($now)) {
            return false;
        }

        if ($offer->end_date && $offer->end_date->lt($now)) {
            return false;
        }

        // 2. Mapping check
        $isMapped = $offer->emiPlans()->where('emi_plans.id', $plan->id)->exists();
        if (!$isMapped) {
            return false;
        }

        return true;
    }

    /**
     * Validate offer rules for a booking/calculation
     *
     * @param Offer $offer
     * @param EmiPlan $plan
     * @param float $grandTotal
     * @return string|null Error message, or null if valid
     */
    public function validateOfferForBooking(Offer $offer, EmiPlan $plan, float $grandTotal): ?string
    {
        if (!$this->isOfferEligible($offer, $plan)) {
            return 'The selected offer is not active or not applicable to this EMI Plan.';
        }

        try {
            $strategy = $this->calculationService->getStrategy($offer->offer_type);
            return $strategy->validate($offer, $plan, $grandTotal);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
