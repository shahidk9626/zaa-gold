<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\EmiPlan;
use App\Models\GoldPrice;
use App\Services\ProductPricingService;
use App\Services\EmiCalculationService;
use Illuminate\Http\Request;

class EmiCalculatorController extends Controller
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
        
        // Fetch active EMI plans
        $emiPlans = EmiPlan::where('status', 'active')->orderBy('display_order')->get();

        return view('admin.emi-calculator.index', compact('products', 'emiPlans'));
    }

    public function calculate(Request $request)
    {
        $request->validate([
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

            // Log activity
            $this->logActivityDirect('calculator_run', "EMI calculator run for Product: {$product->name}, Plan: {$plan->plan_name}, Price: ₹{$productPrice}", $product->id, null, ['product_id' => $product->id, 'emi_plan_id' => $plan->id]);

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

    public function getOutstandingDetails(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'emi_plan_id' => 'required|exists:emi_plans,id',
        ]);

        $product = Product::findOrFail($request->product_id);
        $plan = EmiPlan::findOrFail($request->emi_plan_id);

        $productPrice = $this->pricingService->calculateCurrentProductPrice($product);
        $latestPrice = GoldPrice::where('status', 'active')->latest('effective_date')->first() 
            ?? GoldPrice::latest('effective_date')->first();
        $is22k = strtoupper($product->gold_type) === '22K';
        $pricePerGram = $latestPrice ? ($is22k ? $latestPrice->price_22k : $latestPrice->price_24k) : 0.00;

        $calculations = $this->emiService->calculate($plan, $productPrice);
        $schedule = $this->emiService->generateOutstandingSchedule($plan, $productPrice);

        // Log Calculator Outstanding Preview
        $this->logActivityDirect('calculator_outstanding_preview', "EMI Calculator outstanding statement previewed for Product: {$product->name}, Plan: {$plan->plan_name}", $product->id, null, ['product_id' => $product->id, 'emi_plan_id' => $plan->id]);

        return response()->json([
            'customer_name' => null, // No customer in calculator mode
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

    public function exportOutstandingPdf(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'emi_plan_id' => 'required|exists:emi_plans,id',
        ]);

        $product = Product::findOrFail($request->product_id);
        $plan = EmiPlan::findOrFail($request->emi_plan_id);

        $productPrice = $this->pricingService->calculateCurrentProductPrice($product);
        $latestPrice = GoldPrice::where('status', 'active')->latest('effective_date')->first() 
            ?? GoldPrice::latest('effective_date')->first();
        $is22k = strtoupper($product->gold_type) === '22K';
        $pricePerGram = $latestPrice ? ($is22k ? $latestPrice->price_22k : $latestPrice->price_24k) : 0.00;

        $calculations = $this->emiService->calculate($plan, $productPrice);
        $schedule = $this->emiService->generateOutstandingSchedule($plan, $productPrice);

        // Log Calculator Outstanding PDF download
        $this->logActivityDirect('calculator_pdf_download', "EMI Calculator outstanding statement PDF downloaded for Product: {$product->name}, Plan: {$plan->plan_name}", $product->id, null, ['product_id' => $product->id, 'emi_plan_id' => $plan->id]);

        $pdfData = [
            'customer' => null,
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
        
        return $pdf->download("EMI_Calculator_Statement_{$product->sku}.pdf");
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

        $this->logActivityDirect(
            $request->action,
            $request->description,
            $request->record_id,
            $request->old_data,
            $request->new_data
        );

        return response()->json(['success' => true]);
    }

    protected function logActivityDirect($action, $description, $recordId = null, $old = null, $new = null)
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
            'module_name' => 'emi_calculator',
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
