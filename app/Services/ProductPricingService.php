<?php

namespace App\Services;

use App\Models\Product;
use App\Models\GoldPrice;

class ProductPricingService
{
    /**
     * Calculate current dynamic price for a product based on its Gold Type (24K or 22K)
     */
    public function calculatePrice(Product $product)
    {
        $latestPrice = GoldPrice::where('status', 'active')
            ->latest('effective_date')
            ->first();

        if (!$latestPrice) {
            $latestPrice = GoldPrice::latest('effective_date')->first();
        }

        if (!$latestPrice) {
            return 0.00;
        }

        // Check product gold type and apply corresponding price per gram multiplier
        $is22k = strtoupper($product->gold_type) === '22K';
        $pricePerGram = $is22k ? $latestPrice->price_22k : $latestPrice->price_24k;

        return $product->weight_in_grams * $pricePerGram;
    }

    /**
     * Reusable wrapper calculation method
     */
    public function calculateCurrentProductPrice(Product $product)
    {
        return $this->calculatePrice($product);
    }
}
