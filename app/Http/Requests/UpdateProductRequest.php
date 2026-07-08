<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');

        return [
            'name' => 'required|string|max:255|unique:products,name,' . $productId,
            'sku' => 'required|string|max:255|unique:products,sku,' . $productId,
            'gold_type' => 'required|string|in:24K,22K',
            'weight_in_grams' => 'required|numeric|min:0',
            'purity' => 'required|numeric|min:0|max:100',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'required|string|in:active,inactive',
        ];
    }
}
