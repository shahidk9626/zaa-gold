<?php

namespace App\Http\Controllers\Customer;

use App\Models\Product;
use App\Models\EmiPlan;
use App\Models\GoldPrice;
use App\Services\ProductPricingService;
use App\Services\EmiCalculationService;
use App\Services\BookingService;
use App\Services\CustomerService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PlanController extends CustomerBaseController
{
    protected ProductPricingService $pricingService;
    protected EmiCalculationService $emiService;
    protected BookingService $bookingService;
    protected PaymentService $paymentService;

    public function __construct(
        CustomerService $customerService,
        ProductPricingService $pricingService,
        EmiCalculationService $emiService,
        BookingService $bookingService,
        PaymentService $paymentService
    ) {
        parent::__construct($customerService);
        $this->pricingService = $pricingService;
        $this->emiService = $emiService;
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
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

        $customerId = $this->customerId();
        $purchaseLimit = [
            'limit' => \App\Models\SystemSetting::get('customer_max_purchase_grams', 100.00),
            'purchased' => $this->bookingService->getPurchasedWeightForFinancialYear($customerId),
            'remaining' => $this->bookingService->getRemainingPurchaseLimit($customerId),
        ];

        return view('customer.plans.index', compact('products', 'goldPrice', 'purchaseLimit'));
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
                
                $eligibleOffers = app(\App\Services\OfferEligibilityService::class)->getEligibleOffersForPlan($plan);
                $bestOffer = $eligibleOffers->first();

                // Calculate base plan details without auto-applying any offer by default
                $calc = $this->emiService->calculate($plan, $productPrice, null);
                
                // Set badges: Recommended based on lowest monthly EMI, Popular if marked as default
                $badge = null;
                if ($plan->is_default) {
                    $badge = 'Popular';
                }
                
                $eligiblePlans[] = [
                    'plan' => $plan,
                    'calculations' => $calc,
                    'badge' => $badge,
                    'best_offer' => $bestOffer
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
        
        $eligibleOffers = app(\App\Services\OfferEligibilityService::class)->getEligibleOffersForPlan($plan);
        
        $selectedOffer = null;
        $offerId = request()->input('offer_id');
        if ($offerId !== null && $offerId !== '') {
            if ($offerId !== 'none') {
                $selectedOffer = $eligibleOffers->firstWhere('id', $offerId);
            }
        } else {
            // Default to no offer selected
            $selectedOffer = null;
        }

        $calculations = $this->emiService->calculate($plan, $productPrice, $selectedOffer);
        
        $offersList = $eligibleOffers->map(function($offer) use ($plan, $productPrice) {
            $offerCalc = $this->emiService->calculate($plan, $productPrice, $offer);
            $savingsMsg = '';
            if ($offer->offer_type === 'percentage') {
                $savingsMsg = " (" . (float)$offer->percentage . "% Off - Save ₹" . number_format($offerCalc['discount_amount'], 2) . ")";
            } elseif ($offer->offer_type === 'fixed') {
                $savingsMsg = " (Save ₹" . number_format($offer->fixed_amount, 2) . ")";
            } else {
                $savingsMsg = " (Waive " . $offer->free_emi_count . " EMI - Value ₹" . number_format($offerCalc['savings_amount'], 2) . ")";
            }

            return [
                'id' => $offer->id,
                'offer_name' => $offer->offer_name,
                'savings_message' => $savingsMsg,
            ];
        });
        
        return response()->json(array_merge([
            'product_name' => $product->name,
            'weight_in_grams' => $product->weight_in_grams,
            'purity' => $product->purity,
            'gold_type' => $product->gold_type,
            'product_price' => $productPrice,
            'plan_name' => $plan->plan_name,
            'duration_months' => $plan->duration_months,
            'completion_date' => $calculations['completion_date'],
            'applied_offer_id' => $selectedOffer ? $selectedOffer->id : null,
            'applied_offer_name' => $selectedOffer ? $selectedOffer->offer_name : null,
            'eligible_offers' => $offersList,
            'discount_amount' => $selectedOffer ? ($calculations['discount_amount'] ?? 0.00) : 0.00,
            'savings_amount' => $selectedOffer ? ($calculations['savings_amount'] ?? 0.00) : 0.00,
            'original_amount' => $selectedOffer ? ($calculations['original_amount'] ?? $calculations['total_payable'] ?? 0.00) : 0.00,
            'original_total' => $selectedOffer ? ($calculations['original_amount'] ?? $calculations['total_payable'] ?? 0.00) : 0.00,
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
            'offer_id' => 'nullable|string',
            'remarks' => 'nullable|string',
            'terms' => 'accepted'
        ]);

        $customerId = $this->customerId();
        $product = Product::findOrFail($request->product_id);
        $weight = (float)$product->weight_in_grams;

        if (!$this->bookingService->canPurchaseGold($customerId, $weight)) {
            $purchased = $this->bookingService->getPurchasedWeightForFinancialYear($customerId);
            $limit = \App\Models\SystemSetting::get('customer_max_purchase_grams', 100.00);

            // Log: Purchase Blocked Due To Limit
            \App\Models\ActivityLog::create([
                'module_name' => 'gold_booking',
                'record_id' => $customerId,
                'action_type' => 'purchase_blocked_limit',
                'description' => "Purchase blocked for customer #{$customerId}. Attempted weight: {$weight}g. Already purchased: {$purchased}g. Limit: {$limit}g.",
                'created_by_id' => $customerId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            return back()->with('purchase_limit_error', [
                'purchased' => $purchased,
                'limit' => $limit,
            ])->withInput();
        }

        try {
            $plan = EmiPlan::findOrFail($request->emi_plan_id);
            $eligibleOffers = app(\App\Services\OfferEligibilityService::class)->getEligibleOffersForPlan($plan);
            
            $selectedOffer = null;
            if ($request->filled('offer_id') && $request->offer_id !== 'none' && $request->offer_id !== 'null') {
                $selectedOffer = $eligibleOffers->firstWhere('id', $request->offer_id);
            }
            
            $offerId = $selectedOffer ? $selectedOffer->id : null;

            $booking = $this->bookingService->createDraftBookingForPayment(
                $customerId,
                $request->product_id,
                $request->emi_plan_id,
                $request->remarks,
                $offerId
            );

            $payment = $this->paymentService->initiateBookingGatewayPayment($booking);

            return redirect()->route('customer.booking-payments.checkout', $payment['transaction']->id)
                ->with('success', 'Payment transaction created. Redirecting to secure Cashfree checkout.');
        } catch (\Exception $e) {
            return back()->with('error', 'Booking failed: ' . $e->getMessage())->withInput();
        }
    }
}
