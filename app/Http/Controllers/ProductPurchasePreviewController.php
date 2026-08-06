<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\EmiPlan;
use App\Models\GoldPrice;
use App\Services\ProductPricingService;
use App\Services\EmiCalculationService;
use Illuminate\Http\Request;

class ProductPurchasePreviewController extends Controller
{
    protected $pricingService;
    protected $emiService;

    public function __construct(ProductPricingService $pricingService, EmiCalculationService $emiService)
    {
        $this->pricingService = $pricingService;
        $this->emiService = $emiService;
    }

    public function index()
    {
        // Fetch active products
        $products = Product::where('status', 'active')->orderBy('name')->get();
        
        // Fetch customers (users with customer detail)
        $customers = User::whereHas('customerDetail')->orderBy('name')->get();
        
        // Fetch active EMI plans
        $emiPlans = EmiPlan::where('status', 'active')->orderBy('display_order')->get();

        return view('admin.purchase-preview.index', compact('products', 'customers', 'emiPlans'));
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'emi_plan_id' => 'nullable|exists:emi_plans,id',
            'offer_id' => 'nullable|exists:offers,id',
        ]);

        $product = Product::findOrFail($request->product_id);
        
        // Calculate current dynamic product price
        $productPrice = $this->pricingService->calculateCurrentProductPrice($product);

        // Get live gold price per gram
        $latestPrice = GoldPrice::where('status', 'active')->latest('effective_date')->first();
        if (!$latestPrice) {
            $latestPrice = GoldPrice::latest('effective_date')->first();
        }

        if (!$latestPrice) {
            return response()->json(['error' => 'No active gold price configuration found.'], 422);
        }

        $is22k = strtoupper($product->gold_type) === '22K';
        $pricePerGram = $is22k ? $latestPrice->price_22k : $latestPrice->price_24k;

        if ($request->filled('emi_plan_id')) {
            $plan = EmiPlan::findOrFail($request->emi_plan_id);

            // Validate booking limits
            if ($productPrice < $plan->minimum_booking_amount || $productPrice > $plan->maximum_booking_amount) {
                return response()->json([
                    'error' => "Product price (₹" . number_format($productPrice, 2) . ") must be between the plan's booking amount limits: ₹" . number_format($plan->minimum_booking_amount) . " - ₹" . number_format($plan->maximum_booking_amount)
                ], 422);
            }

            if ($product->weight_in_grams < $plan->minimum_gold_weight || $product->weight_in_grams > $plan->maximum_gold_weight) {
                return response()->json([
                    'error' => "Product weight ({$product->weight_in_grams}g) must be between the plan's gold weight limits: {$plan->minimum_gold_weight}g - {$plan->maximum_gold_weight}g"
                ], 422);
            }

            $eligibleOffers = app(\App\Services\OfferEligibilityService::class)->getEligibleOffersForPlan($plan);

            $offer = null;
            if ($request->filled('offer_id')) {
                $offer = \App\Models\Offer::find($request->offer_id);
            } else {
                // Default to highest priority offer
                $offer = $eligibleOffers->first();
            }

            $calculations = $this->emiService->calculate($plan, $productPrice, $offer);

            return response()->json(array_merge([
                'product_name' => $product->name,
                'weight_in_grams' => $product->weight_in_grams,
                'purity' => $product->purity,
                'gold_type' => $product->gold_type,
                'gold_price_per_gram' => $pricePerGram,
                'product_price' => $productPrice,
                'plan_name' => $plan->plan_name,
                'duration_months' => $plan->duration_months,
                'processing_fee' => $calculations['processing_fee'],
                'interest' => $calculations['interest'],
                'installment' => $calculations['installment'],
                'total_payable' => $calculations['total_payable'],
                'late_fee' => $calculations['late_fee'],
                'completion_date' => $calculations['completion_date'],
                'eligible_offers' => $eligibleOffers->map(function ($o) {
                    return [
                        'id' => $o->id,
                        'offer_name' => $o->offer_name,
                        'offer_code' => $o->offer_code,
                        'offer_type' => $o->offer_type,
                    ];
                })->toArray(),
                'applied_offer_id' => $offer ? $offer->id : null,
            ], $calculations));
        }

        // Otherwise, calculate parameters for ALL active plans to display on the choice list
        $allPlans = EmiPlan::where('status', 'active')->orderBy('display_order')->get();
        $eligiblePlans = [];

        foreach ($allPlans as $plan) {
            // Check if within limits
            if ($productPrice >= $plan->minimum_booking_amount && 
                $productPrice <= $plan->maximum_booking_amount && 
                $product->weight_in_grams >= $plan->minimum_gold_weight && 
                $product->weight_in_grams <= $plan->maximum_gold_weight) {
                
                // Get eligible offers for this plan
                $eligibleOffers = app(\App\Services\OfferEligibilityService::class)->getEligibleOffersForPlan($plan);
                $bestOffer = $eligibleOffers->first();

                $calc = $this->emiService->calculate($plan, $productPrice, $bestOffer);
                $eligiblePlans[] = array_merge(
                    $plan->toArray(),
                    [
                        'processing_fee' => $calc['processing_fee'],
                        'installment' => $calc['installment'],
                        'total_payable' => $calc['total_payable'],
                        'best_offer' => $bestOffer ? [
                            'id' => $bestOffer->id,
                            'offer_name' => $bestOffer->offer_name,
                            'offer_code' => $bestOffer->offer_code,
                            'offer_type' => $bestOffer->offer_type,
                            'free_emi_count' => $bestOffer->free_emi_count,
                            'percentage' => $bestOffer->percentage,
                            'fixed_amount' => $bestOffer->fixed_amount,
                        ] : null,
                    ],
                    $calc
                );
            }
        }

        return response()->json([
            'product_name' => $product->name,
            'sku' => $product->sku,
            'weight_in_grams' => $product->weight_in_grams,
            'purity' => $product->purity,
            'gold_type' => $product->gold_type,
            'gold_price_per_gram' => $pricePerGram,
            'product_price' => $productPrice,
            'thumbnail' => $product->getThumbnailUrl(),
            'gallery_images' => collect($product->gallery_images ?? [])->map(function($path) {
                if (strpos(request()->getHost(), 'aurongold.in') !== false) {
                    return asset('storage/app/public/' . $path);
                }
                return asset('storage/' . $path);
            })->toArray(),
            'description' => $product->description,
            'eligible_plans' => $eligiblePlans,
        ]);
    }

    public function logActivity(Request $request)
    {
        $request->validate([
            'action' => 'required|string',
            'description' => 'required|string',
            'record_id' => 'nullable|integer',
            'old_data' => 'nullable|array',
            'new_data' => 'nullable|array',
        ]);

        $this->logPurchasePreviewActivity(
            $request->action,
            $request->description,
            $request->record_id,
            $request->old_data,
            $request->new_data
        );

        return response()->json(['success' => true]);
    }

    protected function logPurchasePreviewActivity($action, $description, $recordId = null, $old = null, $new = null)
    {
        $userAgent = request()->header('User-Agent');
        $browser = 'Unknown';
        if (!empty($userAgent)) {
            if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) $browser = 'Internet Explorer';
            elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
            elseif (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
            elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
            elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) $browser = 'Opera';
            elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
        }

        \App\Models\ActivityLog::create([
            'module_name' => 'purchase_preview',
            'record_id' => $recordId,
            'action_type' => $action,
            'old_data' => $old,
            'new_data' => $new,
            'description' => $description,
            'created_by_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Fetch EMI Outstanding details as JSON
     */
    public function getOutstandingDetails(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'emi_plan_id' => 'required|exists:emi_plans,id',
            'customer_id' => 'nullable|exists:users,id',
            'offer_id' => 'nullable|exists:offers,id',
        ]);

        $product = Product::findOrFail($request->product_id);
        $plan = EmiPlan::findOrFail($request->emi_plan_id);
        $customer = $request->customer_id ? User::find($request->customer_id) : null;

        $offer = $request->filled('offer_id') ? \App\Models\Offer::find($request->offer_id) : null;

        $productPrice = $this->pricingService->calculateCurrentProductPrice($product);
        $latestPrice = GoldPrice::where('status', 'active')->latest('effective_date')->first() 
            ?? GoldPrice::latest('effective_date')->first();
        $is22k = strtoupper($product->gold_type) === '22K';
        $pricePerGram = $latestPrice ? ($is22k ? $latestPrice->price_22k : $latestPrice->price_24k) : 0.00;

        $calculations = $this->emiService->calculate($plan, $productPrice, $offer);
        $schedule = $this->emiService->generateOutstandingSchedule($plan, $productPrice, $offer);

        $customerName = $customer ? $customer->name : 'N/A';
        $this->logPurchasePreviewActivity(
            'outstanding_preview',
            "Outstanding Statement previewed for Customer: {$customerName}, Product: {$product->name}, Plan: {$plan->plan_name}",
            $product->id,
            null,
            ['customer_id' => $request->customer_id, 'product_id' => $request->product_id, 'emi_plan_id' => $request->emi_plan_id, 'offer_id' => $request->offer_id]
        );

        return response()->json([
            'customer_name' => $customer ? $customer->name : null,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'weight_in_grams' => $product->weight_in_grams,
            'purity' => $product->purity,
            'gold_type' => $product->gold_type,
            'gold_price_per_gram' => $pricePerGram,
            'product_price' => $productPrice,
            'plan_name' => $plan->plan_name,
            'duration_months' => $plan->duration_months,
            'finance_charge' => $calculations['finance_charge'],
            'storage_charge' => $calculations['storage_charge'],
            'gst_on_gold' => $calculations['gst_on_gold'],
            'gst_on_charges' => $calculations['gst_on_charges'],
            'grand_total' => $calculations['total_payable'],
            'monthly_emi' => $calculations['installment'],
            'schedule' => $schedule
        ]);
    }

    /**
     * Export EMI Outstanding statement as PDF
     */
    public function exportOutstandingPdf(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'emi_plan_id' => 'required|exists:emi_plans,id',
            'customer_id' => 'nullable|exists:users,id',
            'offer_id' => 'nullable|exists:offers,id',
        ]);

        $product = Product::findOrFail($request->product_id);
        $plan = EmiPlan::findOrFail($request->emi_plan_id);
        $customer = $request->customer_id ? User::find($request->customer_id) : null;

        $offer = $request->filled('offer_id') ? \App\Models\Offer::find($request->offer_id) : null;

        $productPrice = $this->pricingService->calculateCurrentProductPrice($product);
        $latestPrice = GoldPrice::where('status', 'active')->latest('effective_date')->first() 
            ?? GoldPrice::latest('effective_date')->first();
        $is22k = strtoupper($product->gold_type) === '22K';
        $pricePerGram = $latestPrice ? ($is22k ? $latestPrice->price_22k : $latestPrice->price_24k) : 0.00;

        $calculations = $this->emiService->calculate($plan, $productPrice, $offer);
        $schedule = $this->emiService->generateOutstandingSchedule($plan, $productPrice, $offer);

        $customerName = $customer ? $customer->name : 'N/A';
        $this->logPurchasePreviewActivity(
            'outstanding_pdf_download',
            "Outstanding Statement PDF downloaded for Customer: {$customerName}, Product: {$product->name}, Plan: {$plan->plan_name}",
            $product->id,
            null,
            ['customer_id' => $request->customer_id, 'product_id' => $request->product_id, 'emi_plan_id' => $request->emi_plan_id, 'offer_id' => $request->offer_id]
        );

        $pdfData = [
            'customer' => $customer,
            'product' => $product,
            'plan' => $plan,
            'pricePerGram' => $pricePerGram,
            'productPrice' => $productPrice,
            'calculations' => $calculations,
            'schedule' => $schedule,
            'generatedAt' => now()->format('d M Y, h:i A'),
            'generatedBy' => auth()->user()->name
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.purchase-preview.outstanding-pdf', $pdfData);
        
        return $pdf->download("EMI_Outstanding_Statement_{$product->sku}.pdf");
    }
}
