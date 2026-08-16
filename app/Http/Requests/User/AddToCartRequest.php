<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'exists:product_variants,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'variant_id.required' => 'الفاريانت مطلوب',
            'variant_id.exists'   => 'الفاريانت غير موجود',

            'quantity.required'   => 'الكمية مطلوبة',
            'quantity.min'        => 'الكمية يجب أن تكون 1 على الأقل',
        ];
    }
}
