<?php

namespace App\Http\Controllers;

use App\Models\EmiPlan;
use App\Http\Requests\StoreEmiPlanRequest;
use App\Http\Requests\UpdateEmiPlanRequest;
use App\Services\EmiCalculationService;
use Illuminate\Http\Request;

class EmiPlanController extends Controller
{
    protected $calculationService;

    public function __construct(EmiCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $plans = EmiPlan::latest()->get();
            return response()->json(['data' => $plans]);
        }
        return view('admin.emi-plans.index');
    }

    public function create()
    {
        return view('admin.emi-plans.create');
    }

    public function store(StoreEmiPlanRequest $request)
    {
        $data = $request->validated();
        $data['is_default'] = $request->has('is_default') ? (bool)$request->is_default : false;
        $data['gst_on_gold_enabled'] = $request->has('gst_on_gold_enabled') ? (bool)$request->gst_on_gold_enabled : false;
        $data['gst_on_charges_enabled'] = $request->has('gst_on_charges_enabled') ? (bool)$request->gst_on_charges_enabled : false;
        $data['finance_charge_enabled'] = $request->has('finance_charge_enabled') ? (bool)$request->finance_charge_enabled : false;
        $data['storage_charge_enabled'] = $request->has('storage_charge_enabled') ? (bool)$request->storage_charge_enabled : false;

        $data['interest_type'] = $data['interest_type'] ?? 'flat';
        $data['interest_rate'] = $data['interest_rate'] ?? 0.00;
        $data['processing_fee_type'] = $data['processing_fee_type'] ?? 'fixed';
        $data['processing_fee'] = $data['processing_fee'] ?? 0.00;

        $plan = EmiPlan::create($data);

        return response()->json(['success' => 'EMI Plan created successfully', 'plan' => $plan]);
    }

    public function show($id)
    {
        $plan = EmiPlan::findOrFail($id);
        return view('admin.emi-plans.show', compact('plan'));
    }

    public function edit($id)
    {
        $plan = EmiPlan::findOrFail($id);
        return view('admin.emi-plans.edit', compact('plan'));
    }

    public function update(UpdateEmiPlanRequest $request, $id)
    {
        $plan = EmiPlan::findOrFail($id);
        $data = $request->validated();
        $data['is_default'] = $request->has('is_default') ? (bool)$request->is_default : false;
        $data['gst_on_gold_enabled'] = $request->has('gst_on_gold_enabled') ? (bool)$request->gst_on_gold_enabled : false;
        $data['gst_on_charges_enabled'] = $request->has('gst_on_charges_enabled') ? (bool)$request->gst_on_charges_enabled : false;
        $data['finance_charge_enabled'] = $request->has('finance_charge_enabled') ? (bool)$request->finance_charge_enabled : false;
        $data['storage_charge_enabled'] = $request->has('storage_charge_enabled') ? (bool)$request->storage_charge_enabled : false;

        $data['interest_type'] = $data['interest_type'] ?? 'flat';
        $data['interest_rate'] = $data['interest_rate'] ?? 0.00;
        $data['processing_fee_type'] = $data['processing_fee_type'] ?? 'fixed';
        $data['processing_fee'] = $data['processing_fee'] ?? 0.00;

        $plan->update($data);

        return response()->json(['success' => 'EMI Plan updated successfully']);
    }

    public function toggleStatus($id)
    {
        $plan = EmiPlan::findOrFail($id);
        $plan->status = $plan->status === 'active' ? 'inactive' : 'active';
        $plan->save();

        return response()->json(['success' => 'Status updated successfully']);
    }

    public function destroy($id)
    {
        $plan = EmiPlan::findOrFail($id);
        $plan->delete();

        return response()->json(['success' => 'EMI Plan deleted successfully']);
    }

    /**
     * AJAX endpoint to run simulation calculations
     */
    public function simulate(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $plan = EmiPlan::findOrFail($id);
        $amount = (float)$request->amount;

        $goldValue = $amount;
        $gstOnGold = $this->calculationService->calculateGSTOnGold($plan, $goldValue);
        $financeCharge = $this->calculationService->calculateFinanceCharge($plan, $goldValue);
        $storageCharge = $this->calculationService->calculateStorageCharge($plan, $goldValue);
        $totalCharges = $this->calculationService->calculateTotalCharges($plan, $financeCharge, $storageCharge);
        $gstOnCharges = $this->calculationService->calculateGSTOnCharges($plan, $storageCharge);
        $grandTotal = $this->calculationService->calculateGrandTotal($plan, $goldValue, $gstOnGold, $totalCharges, $gstOnCharges);
        $monthlyEMI = $this->calculationService->calculateMonthlyEMI($plan, $grandTotal);

        $useFinancialEngine = (bool)($plan->gst_on_gold_enabled || $plan->finance_charge_enabled || $plan->storage_charge_enabled || $plan->gst_on_charges_enabled);

        if ($useFinancialEngine) {
            $installment = $monthlyEMI;
            $totalPayable = $grandTotal;
        } else {
            $installment = $this->calculationService->calculateMonthlyInstallment($plan, $amount);
            $totalPayable = $amount + $this->calculationService->calculateInterest($plan, $amount);
        }

        $processingFee = $this->calculationService->calculateProcessingFee($plan, $amount);
        $interest = $this->calculationService->calculateInterest($plan, $amount);
        $lateFee = $this->calculationService->calculateLateFee($plan, $installment);
        $completionDate = $this->calculationService->calculateCompletionDate($plan, now())->format('Y-m-d');

        return response()->json([
            'installment' => $installment,
            'processing_fee' => $processingFee,
            'interest' => $interest,
            'total_payable' => $totalPayable,
            'late_fee' => $lateFee,
            'completion_date' => $completionDate,
            
            // New calculation details
            'use_financial_engine' => $useFinancialEngine,
            'gold_value' => $goldValue,
            'gst_on_gold' => $gstOnGold,
            'finance_charge' => $financeCharge,
            'storage_charge' => $storageCharge,
            'total_charges' => $totalCharges,
            'gst_on_charges' => $gstOnCharges,
            'grand_total' => $grandTotal,
            'gst_on_gold_enabled' => (bool)$plan->gst_on_gold_enabled,
            'finance_charge_enabled' => (bool)$plan->finance_charge_enabled,
            'storage_charge_enabled' => (bool)$plan->storage_charge_enabled,
            'gst_on_charges_enabled' => (bool)$plan->gst_on_charges_enabled,
        ]);
    }
}
