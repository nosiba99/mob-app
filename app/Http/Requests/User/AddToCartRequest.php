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
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['required', 'exists:product_variants,id'],
            'size_id'    => ['required', 'exists:product_variant_sizes,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'المنتج مطلوب',
            'product_id.exists'   => 'المنتج غير موجود',

            'variant_id.required' => 'الفاريانت مطلوب',
            'variant_id.exists'   => 'الفاريانت غير موجود',

            'size_id.required'    => 'المقاس مطلوب',
            'size_id.exists'      => 'المقاس غير موجود',

            'quantity.required'   => 'الكمية مطلوبة',
            'quantity.min'        => 'الكمية يجب أن تكون 1 على الأقل',
        ];
    }
}
