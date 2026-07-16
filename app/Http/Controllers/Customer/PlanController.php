<?php

namespace App\Http\Controllers\Customer;

use App\Models\Product;
use App\Models\EmiPlan;
use App\Models\GoldPrice;
use App\Services\ProductPricingService;
use App\Services\EmiCalculationService;
use App\Services\BookingService;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PlanController extends CustomerBaseController
{
    protected ProductPricingService $pricingService;
    protected EmiCalculationService $emiService;
    protected BookingService $bookingService;

    public function __construct(
        CustomerService $customerService,
        ProductPricingService $pricingService,
        EmiCalculationService $emiService,
        BookingService $bookingService
    ) {
        parent::__construct($customerService);
        $this->pricingService = $pricingService;
        $this->emiService = $emiService;
        $this->bookingService = $bookingService;
    }

    /**
     * Display the Plan Marketplace (Browse products)
     */
    public function index(Request $request): View
    {
        $goldPrice = $this->customerService->getGoldPriceWithTrend();
        
        // Fetch active products
        $products = Product::where('status', 'active')->orderBy('display_order')->get();
        
        // Enrich products with computed price and starting EMI
        foreach ($products as $product) {
            $productPrice = $this->pricingService->calculateCurrentProductPrice($product);
            $product->computed_price = $productPrice;
            
            // Find starting EMI
            $cheapest = null;
            $allPlans = EmiPlan::where('status', 'active')->orderBy('display_order')->get();
            foreach ($allPlans as $plan) {
                if ($productPrice >= $plan->minimum_booking_amount && 
                    $productPrice <= $plan->maximum_booking_amount && 
                    $product->weight_in_grams >= $plan->minimum_gold_weight && 
                    $product->weight_in_grams <= $plan->maximum_gold_weight) {
                    
                    $emi = $this->emiService->calculateMonthlyInstallment($plan, $productPrice);
                    if ($cheapest === null || $emi < $cheapest) {
                        $cheapest = $emi;
                    }
                }
            }
            $product->starting_emi = $cheapest;
        }

        // Apply filters in PHP (since price calculations are dynamic in database)
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $products = $products->filter(function($p) use ($search) {
                return str_contains(strtolower($p->name), $search) 
                    || str_contains(strtolower($p->sku), $search) 
                    || str_contains(strtolower($p->description ?? ''), $search);
            });
        }

        if ($request->filled('purity')) {
            $purity = $request->purity;
            $products = $products->filter(function($p) use ($purity) {
                if ($purity === '22K') {
                    return strtoupper($p->gold_type) === '22K' || $p->purity <= 92;
                } elseif ($purity === '24K') {
                    return strtoupper($p->gold_type) === '24K' || $p->purity > 92;
                }
                return true;
            });
        }

        if ($request->filled('weight_range')) {
            $range = $request->weight_range;
            $products = $products->filter(function($p) use ($range) {
                if ($range === 'under_10') return $p->weight_in_grams < 10;
                if ($range === '10_50') return $p->weight_in_grams >= 10 && $p->weight_in_grams <= 50;
                if ($range === 'above_50') return $p->weight_in_grams > 50;
                return true;
            });
        }

        if ($request->filled('min_price')) {
            $minPrice = (float) $request->min_price;
            $products = $products->filter(fn($p) => $p->computed_price >= $minPrice);
        }

        if ($request->filled('max_price')) {
            $maxPrice = (float) $request->max_price;
            $products = $products->filter(fn($p) => $p->computed_price <= $maxPrice);
        }

        if ($request->filled('duration')) {
            $duration = (int) $request->duration;
            $products = $products->filter(function($p) use ($duration) {
                $plans = EmiPlan::where('status', 'active')->where('duration_months', $duration)->get();
                foreach ($plans as $plan) {
                    if ($p->computed_price >= $plan->minimum_booking_amount && 
                        $p->computed_price <= $plan->maximum_booking_amount && 
                        $p->weight_in_grams >= $plan->minimum_gold_weight && 
                        $p->weight_in_grams <= $plan->maximum_gold_weight) {
                        return true;
                    }
                }
                return false;
            });
        }

        return view('customer.plans.index', compact('products', 'goldPrice'));
    }

    /**
     * Show Product Details & available EMI Plans
     */
    public function show($id): View
    {
        $product = Product::where('status', 'active')->findOrFail($id);
        $productPrice = $this->pricingService->calculateCurrentProductPrice($product);
        $goldPrice = $this->customerService->getGoldPriceWithTrend();
        
        // Find all active eligible plans
        $allPlans = EmiPlan::where('status', 'active')->orderBy('display_order')->get();
        $eligiblePlans = [];
        $cheapestPlanId = null;
        $lowestEmi = null;

        foreach ($allPlans as $plan) {
            if ($productPrice >= $plan->minimum_booking_amount && 
                $productPrice <= $plan->maximum_booking_amount && 
                $product->weight_in_grams >= $plan->minimum_gold_weight && 
                $product->weight_in_grams <= $plan->maximum_gold_weight) {
                
                $calc = $this->emiService->calculate($plan, $productPrice);
                
                // Set badges: Recommended based on lowest monthly EMI, Popular if marked as default
                $badge = null;
                if ($plan->is_default) {
                    $badge = 'Popular';
                }
                
                $eligiblePlans[] = [
                    'plan' => $plan,
                    'calculations' => $calc,
                    'badge' => $badge
                ];

                if ($lowestEmi === null || $calc['installment'] < $lowestEmi) {
                    $lowestEmi = $calc['installment'];
                    $cheapestPlanId = $plan->id;
                }
            }
        }

        // Apply "Recommended" badge to the plan with the lowest EMI or best overall value
        foreach ($eligiblePlans as &$pData) {
            if ($pData['plan']->id === $cheapestPlanId) {
                if ($pData['badge'] === null) {
                    $pData['badge'] = 'Recommended';
                } else {
                    $pData['badge'] = 'Recommended & Popular';
                }
            }
        }
        unset($pData);

        return view('customer.plans.show', compact('product', 'productPrice', 'eligiblePlans', 'goldPrice', 'cheapestPlanId'));
    }

    /**
     * AJAX endpoint to fetch calculations for a specific product and EMI plan
     */
    public function calculatePriceSheet($productId, $planId): JsonResponse
    {
        $product = Product::where('status', 'active')->findOrFail($productId);
        $plan = EmiPlan::where('status', 'active')->findOrFail($planId);
        $productPrice = $this->pricingService->calculateCurrentProductPrice($product);
        
        $calculations = $this->emiService->calculate($plan, $productPrice);
        
        return response()->json(array_merge([
            'product_name' => $product->name,
            'weight_in_grams' => $product->weight_in_grams,
            'purity' => $product->purity,
            'gold_type' => $product->gold_type,
            'product_price' => $productPrice,
            'completion_date' => $calculations['completion_date'],
        ], $calculations));
    }

    /**
     * AJAX live gold price updates
     */
    public function livePrice(): JsonResponse
    {
        $goldPrice = $this->customerService->getGoldPriceWithTrend();
        if ($goldPrice['price']) {
            return response()->json([
                'price_22k' => number_format($goldPrice['price']->price_22k, 2),
                'price_24k' => number_format($goldPrice['price']->price_24k, 2),
                'last_updated' => $goldPrice['price']->effective_date->format('d M Y, h:i A'),
                'trend_22k' => $goldPrice['trend_22k'],
                'trend_24k' => $goldPrice['trend_24k'],
            ]);
        }
        return response()->json(['error' => 'Gold price not available'], 404);
    }

    /**
     * Proceed to Booking (Submit Purchase)
     */
    public function book(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'emi_plan_id' => 'required|exists:emi_plans,id',
            'remarks' => 'nullable|string',
            'terms' => 'accepted'
        ]);

        try {
            $booking = $this->bookingService->createBooking(
                $this->customerId(),
                $request->product_id,
                $request->emi_plan_id,
                $request->remarks
            );

            return redirect()->route('customer.my-plans.show', $booking->id)
                ->with('success', 'Gold Plan booked successfully! Your Price Lock Certificate has been generated.');
        } catch (\Exception $e) {
            return back()->with('error', 'Booking failed: ' . $e->getMessage())->withInput();
        }
    }
}
