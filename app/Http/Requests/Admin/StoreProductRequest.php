<?php

namespace App\Http\Requests\Admin;

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
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric'],
            'category_id' => ['required', 'exists:categories,id'],

            // الفاريانت
            'variants' => ['required', 'array'],
            'variants.*.color_id' => ['required', 'exists:colors,id'],
            'variants.*.sizes' => ['required', 'array'],
            'variants.*.sizes.*.size_id' => ['required', 'exists:sizes,id'],
            'variants.*.sizes.*.stock' => ['required', 'integer', 'min:0'],
        ];
    }

    // تحويل JSON إلى Array
    protected function prepareForValidation()
    {
        if (is_string($this->variants)) {
            $this->merge([
                'variants' => json_decode($this->variants, true)
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المنتج مطلوب',
            'price.required' => 'السعر مطلوب',
            'category_id.exists' => 'التصنيف غير موجود',

            'variants.required' => 'يجب إضافة فاريانت واحد على الأقل',
            'variants.*.color_id.required' => 'لون الفاريانت مطلوب',
            'variants.*.color_id.exists' => 'اللون غير موجود',

            'variants.*.sizes.required' => 'يجب إضافة مقاس واحد على الأقل',
            'variants.*.sizes.*.size_id.required' => 'المقاس مطلوب',
            'variants.*.sizes.*.size_id.exists' => 'المقاس غير موجود',

            'variants.*.sizes.*.stock.required' => 'الكمية مطلوبة',
            'variants.*.sizes.*.stock.min' => 'الكمية يجب أن تكون 0 أو أكثر',
        ];
    }
}
