<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\EmiPlan;
use App\Offers\Strategies\OfferTypeStrategy;
use App\Offers\Strategies\PercentageDiscountStrategy;
use App\Offers\Strategies\FixedAmountDiscountStrategy;
use App\Offers\Strategies\EmiDiscountStrategy;

class OfferCalculationService
{
    protected array $strategies = [];

    public function __construct()
    {
        $this->registerStrategy('percentage', new PercentageDiscountStrategy());
        $this->registerStrategy('fixed', new FixedAmountDiscountStrategy());
        $this->registerStrategy('emi', new EmiDiscountStrategy());
    }

    public function registerStrategy(string $type, OfferTypeStrategy $strategy): void
    {
        $this->strategies[strtolower($type)] = $strategy;
    }

    public function getStrategy(string $type): OfferTypeStrategy
    {
        $type = strtolower($type);
        if (!isset($this->strategies[$type])) {
            throw new \InvalidArgumentException("Offer type Strategy [{$type}] not supported.");
        }
        return $this->strategies[$type];
    }

    /**
     * Apply the selected offer to raw plan calculations
     *
     * @param Offer $offer
     * @param array $calculations Original calculations array from EmiCalculationService
     * @param EmiPlan $plan
     * @return array Updated calculations with offer snapshot parameters
     */
    public function applyOffer(Offer $offer, array $calculations, EmiPlan $plan): array
    {
        $strategy = $this->getStrategy($offer->offer_type);
        
        $grandTotal = (float) ($calculations['grand_total'] ?? $calculations['total_payable'] ?? 0.00);
        $monthlyEmi = (float) ($calculations['installment'] ?? 0.00);
        $duration = (int) $plan->duration_months;

        $res = $strategy->calculate($offer, $grandTotal, $monthlyEmi, $duration);

        // Populate offer attributes
        $calculations['offer_id'] = $offer->id;
        $calculations['offer_name'] = $offer->offer_name;
        $calculations['offer_type'] = $offer->offer_type;
        
        if ($offer->offer_type === 'percentage') {
            $calculations['offer_value'] = $offer->percentage;
        } elseif ($offer->offer_type === 'fixed') {
            $calculations['offer_value'] = $offer->fixed_amount;
        } else {
            $calculations['offer_value'] = $offer->free_emi_count;
        }

        $calculations['original_amount'] = $grandTotal;
        $calculations['discount_amount'] = $res['discount_amount'];
        $calculations['final_amount'] = $res['final_amount'];
        $calculations['savings_amount'] = $res['savings_amount'];
        $calculations['waived_emi_count'] = $res['waived_emi_count'];
        $calculations['offer_snapshot'] = $offer->toArray();

        // Adjust payable parameters based on offer type
        if (in_array($offer->offer_type, ['percentage', 'fixed'])) {
            $calculations['grand_total'] = $res['final_amount'];
            $calculations['total_payable'] = $res['final_amount'];
            $calculations['installment'] = round($res['final_amount'] / $duration, 2);
        } else {
            // For EMI Discounts: Grand total/installment are NOT reduced (waived EMIs are generated as 0 later)
            // But final amount represents the discounted sum they will pay in total.
            $calculations['grand_total'] = $grandTotal;
            $calculations['total_payable'] = $grandTotal;
            // Monthly EMI remains normal
        }

        return $calculations;
    }
}
