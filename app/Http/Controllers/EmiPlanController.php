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

        $installment = $this->calculationService->calculateMonthlyInstallment($plan, $amount);
        $processingFee = $this->calculationService->calculateProcessingFee($plan, $amount);
        $interest = $this->calculationService->calculateInterest($plan, $amount);
        $totalPayable = $amount + $interest;
        $lateFee = $this->calculationService->calculateLateFee($plan, $installment);
        $completionDate = $this->calculationService->calculateCompletionDate($plan, now())->format('Y-m-d');

        return response()->json([
            'installment' => $installment,
            'processing_fee' => $processingFee,
            'interest' => $interest,
            'total_payable' => $totalPayable,
            'late_fee' => $lateFee,
            'completion_date' => $completionDate,
        ]);
    }
}
