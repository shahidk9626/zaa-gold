<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellOldGoldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'mobile' => 'required|string',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'gold_type' => 'required|string|in:18K,22K,24K',
            'estimated_weight' => 'required|numeric|min:0',
            'estimated_value' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
            'status' => 'required|string|in:New,Contacted,Inspection Scheduled,Quoted,Accepted,Rejected,Closed',
            'assigned_to' => 'nullable|exists:users,id',
            'followup_date' => 'nullable|date',
        ];
    }
}
