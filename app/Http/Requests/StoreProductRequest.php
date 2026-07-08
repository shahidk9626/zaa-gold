<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:products,name',
            'sku' => 'required|string|max:255|unique:products,sku',
            'weight' => 'required|numeric|min:0',
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
