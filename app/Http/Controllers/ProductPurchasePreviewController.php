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

            $calculations = $this->emiService->calculate($plan, $productPrice);

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
                
                $calc = $this->emiService->calculate($plan, $productPrice);
                $eligiblePlans[] = array_merge(
                    $plan->toArray(),
                    [
                        'processing_fee' => $calc['processing_fee'],
                        'installment' => $calc['installment'],
                        'total_payable' => $calc['total_payable'],
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
            'thumbnail' => $product->thumbnail ? asset('storage/' . $product->thumbnail) : null,
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
}
