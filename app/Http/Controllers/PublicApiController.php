<?php

namespace App\Http\Controllers;

use App\Models\GoldPrice;
use App\Models\Product;
use Illuminate\Http\Request;

class PublicApiController extends Controller
{
    /**
     * Get today's active gold price.
     */
    public function getGoldPrice(Request $request)
    {
        $latestPrice = GoldPrice::where('status', 'active')
            ->latest('effective_date')
            ->first();

        // Fallback to the latest price if no active price is found
        if (!$latestPrice) {
            $latestPrice = GoldPrice::latest('effective_date')->first();
        }

        if (!$latestPrice) {
            return response()->json([
                'success' => false,
                'message' => 'Gold prices are temporarily unavailable.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'gold_22k' => (float) $latestPrice->price_22k,
                'gold_24k' => (float) $latestPrice->price_24k,
                'gold_bullion' => (float) $latestPrice->price_bullion,
                'effective_at' => $latestPrice->effective_date ? $latestPrice->effective_date->format('Y-m-d H:i:s') : null,
                'status' => $latestPrice->status,
            ]
        ]);
    }

    /**
     * Get active products for customer-facing display.
     */
    public function getProducts(Request $request)
    {
        $products = Product::where('status', 'active')
            ->orderBy('display_order')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'gold_type' => $product->gold_type,
                    'weight_in_grams' => (float) $product->weight_in_grams,
                    'purity' => (float) $product->purity,
                    'category' => $product->category,
                    'description' => $product->description,
                    'thumbnail_url' => $product->getThumbnailUrl(),
                    'calculated_price' => (float) $product->calculated_price,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a new website enquiry.
     */
    public function storeWebsiteEnquiry(Request $request, \App\Services\WebsiteEnquiryService $service)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:20',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please check the submitted information.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $service->createEnquiry($validator->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Thank you for contacting AurOnGold. Your enquiry has been submitted successfully.'
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Website enquiry storage failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while submitting your enquiry. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get system maintenance status.
     */
    public function getSystemStatus(\App\Services\MaintenanceModeService $service)
    {
        return response()->json([
            'success' => true,
            'maintenance_mode' => $service->isEnabled()
        ]);
    }
}
