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
            'processing_fee_type' => 'nullable|string|in:percent,fixed',
            'processing_fee' => 'nullable|numeric|min:0',
            'interest_type' => 'nullable|string|in:flat,reducing',
            'interest_rate' => 'nullable|numeric|min:0',
            'late_fee_type' => 'required|string|in:percent,fixed',
            'late_fee' => 'required|numeric|min:0',
            'grace_days' => 'required|integer|min:0',
            'auto_terminate_after_missed_emi' => 'required|integer|min:0',
            'maintenance_deduction_percent' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'status' => 'required|string|in:active,inactive',
            'is_default' => 'nullable|boolean',
            'gst_on_gold_enabled' => 'nullable|boolean',
            'gst_on_gold_percent' => 'required_if:gst_on_gold_enabled,1|nullable|numeric|min:0|max:100',
            'finance_charge_enabled' => 'nullable|boolean',
            'finance_charge_type' => 'required_if:finance_charge_enabled,1|nullable|string|in:percentage,fixed,percent,Percentage,Fixed',
            'finance_charge_value' => [
                'required_if:finance_charge_enabled,1',
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $type = $this->input('finance_charge_type');
                    if (in_array(strtolower($type), ['percentage', 'percent']) && ($value < 0 || $value > 100)) {
                        $fail('The finance charge percentage must be between 0 and 100.');
                    }
                }
            ],
            'storage_charge_enabled' => 'nullable|boolean',
            'storage_charge_type' => 'required_if:storage_charge_enabled,1|nullable|string|in:percentage,fixed,percent,Percentage,Fixed',
            'storage_charge_value' => [
                'required_if:storage_charge_enabled,1',
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $type = $this->input('storage_charge_type');
                    if (in_array(strtolower($type), ['percentage', 'percent']) && ($value < 0 || $value > 100)) {
                        $fail('The storage charge percentage must be between 0 and 100.');
                    }
                }
            ],
            'gst_on_charges_enabled' => 'nullable|boolean',
            'gst_on_charges_percent' => 'required_if:gst_on_charges_enabled,1|nullable|numeric|min:0|max:100',
            'rounding_type' => 'required|string|in:none,nearest_rupee,nearest_10,nearest_100,None,Nearest Rupee,Nearest 10,Nearest 100',
        ];
    }
}
