<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoldPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'price_bullion' => $this->price_bullion ?? $this->price_24k ?? 0.00,
        ]);
    }

    public function rules(): array
    {
        return [
            'price_22k' => 'required|numeric|min:0',
            'price_24k' => 'required|numeric|min:0',
            'price_bullion' => 'required|numeric|min:0',
            'effective_date' => 'required|date',
            'remarks' => 'nullable|string|max:255',
            'status' => 'required|string|in:active,inactive',
        ];
    }
}
