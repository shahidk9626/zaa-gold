<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFranchiseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'mobile' => 'required|string',
            'email' => 'required|email|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'investment_budget' => 'required|string|max:255',
            'business_experience' => 'nullable|string',
            'current_business' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'status' => 'required|string|in:New,Contacted,Meeting Scheduled,Proposal Sent,Approved,Rejected,Closed',
            'assigned_to' => 'nullable|exists:users,id',
            'followup_date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ];
    }
}
