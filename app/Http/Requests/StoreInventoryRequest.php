<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $uniqueRule = $id ? 'unique:inventories,product_id,' . $id : 'unique:inventories,product_id';

        return [
            'product_id' => 'required|exists:products,id|' . $uniqueRule,
            'available_qty' => 'required|numeric|min:0',
            'reserved_qty' => 'nullable|numeric|min:0',
            'sold_qty' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
            'status' => 'required|string|in:active,inactive',
        ];
    }
}
