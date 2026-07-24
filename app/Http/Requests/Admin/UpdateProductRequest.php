<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'price'       => ['sometimes', 'numeric'],
            'category_id' => ['sometimes', 'exists:categories,id'],

            // الصور
            'main_image' => ['sometimes', 'image', 'max:4096'],
            'images'     => ['sometimes', 'array'],
            'images.*'   => ['image', 'max:4096'],

            // الفاريانت
            'variants' => ['sometimes', 'array', 'min:1'],
            'variants.*.color_id'        => ['required_with:variants', 'exists:colors,id'],
            'variants.*.sizes'           => ['required_with:variants', 'array'],
            'variants.*.sizes.*.size_id' => ['required_with:variants', 'exists:sizes,id'],
            'variants.*.sizes.*.stock'   => ['required_with:variants', 'integer', 'min:0'],
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
            'variants.min' => 'يجب إضافة فاريانت واحد على الأقل',

            'variants.*.color_id.required_with' => 'لون الفاريانت مطلوب',
            'variants.*.color_id.exists'        => 'اللون غير موجود',

            'variants.*.sizes.required_with' => 'يجب إضافة مقاس واحد على الأقل',
            'variants.*.sizes.*.size_id.exists' => 'المقاس غير موجود',

            'variants.*.sizes.*.stock.required_with' => 'الكمية مطلوبة',
            'variants.*.sizes.*.stock.min'           => 'الكمية يجب أن تكون 0 أو أكثر',
        ];
    }
}
