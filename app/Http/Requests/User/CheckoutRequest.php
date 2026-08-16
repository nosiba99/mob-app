<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

   public function rules()
{
    return [
        'shipping_address' => 'required|string|max:255',
        'notes' => 'nullable|string'
    ];
}


    public function messages(): array
    {
        return [
            'address_id.required' => 'العنوان مطلوب',
            'address_id.exists'   => 'العنوان غير موجود',

            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.in'       => 'طريقة الدفع يجب أن تكون cash أو card',
        ];
    }
}
