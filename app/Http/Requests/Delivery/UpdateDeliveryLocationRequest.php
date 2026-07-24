<?php

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'خط العرض مطلوب',
            'lng.required' => 'خط الطول مطلوب',
        ];
    }
}
