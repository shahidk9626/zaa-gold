<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $referralId = $this->route('id') ?? $this->id;

        return [
            'referral_code' => 'required|string|unique:referrals,referral_code,' . $referralId,
            'referrer_customer_id' => 'required|exists:users,id',
            'referred_customer_id' => 'required|exists:users,id|different:referrer_customer_id',
            'booking_id' => 'nullable|exists:gold_bookings,id',
            'reward_type' => 'required|string|in:Cash,Gold Grams,Discount',
            'reward_amount' => 'required|numeric|min:0',
            'reward_status' => 'required|string|in:Pending,Eligible,Rewarded,Rejected',
            'remarks' => 'nullable|string',
        ];
    }
}
