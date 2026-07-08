<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $isUpdate = (bool)$id;

        return [
            'user_id' => 'required|exists:users,id',
            'document_type' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'front_image' => $isUpdate ? 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048' : 'required|image|mimes:jpeg,png,jpg,pdf|max:2048',
            'back_image' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:2048',
            'selfie' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
