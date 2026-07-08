<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmiPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_name' => 'required|string|max:255|unique:emi_plans,plan_name',
            'plan_code' => 'required|string|max:255|unique:emi_plans,plan_code',
            'duration_months' => 'required|integer|min:1',
            'minimum_booking_amount' => 'required|numeric|min:0',
            'maximum_booking_amount' => 'required|numeric|gte:minimum_booking_amount',
            'minimum_gold_weight' => 'required|numeric|min:0',
            'maximum_gold_weight' => 'required|numeric|gte:minimum_gold_weight',
            'processing_fee_type' => 'required|string|in:percent,fixed',
            'processing_fee' => 'required|numeric|min:0',
            'interest_type' => 'required|string|in:flat,reducing',
            'interest_rate' => 'required|numeric|min:0',
            'late_fee_type' => 'required|string|in:percent,fixed',
            'late_fee' => 'required|numeric|min:0',
            'grace_days' => 'required|integer|min:0',
            'auto_terminate_after_missed_emi' => 'required|integer|min:0',
            'maintenance_deduction_percent' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'status' => 'required|string|in:active,inactive',
            'is_default' => 'nullable|boolean',
        ];
    }
}
