<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric'],
            'category_id' => ['required', 'exists:categories,id'],

            // ❌ حذف التحقق من الصور لأن الصور ستأتي في طلب منفصل
            // 'main_image' => ['required', 'image', 'max:4096'],
            // 'images' => ['nullable', 'array'],
            // 'images.*' => ['image', 'max:4096'],

            // ✔ الفاريانت
            'variants' => ['required', 'array'],
            'variants.*.color_id' => ['required', 'exists:colors,id'],
            'variants.*.sizes' => ['required', 'array'],
            'variants.*.sizes.*.size_id' => ['required', 'exists:sizes,id'],
            'variants.*.sizes.*.stock' => ['required', 'integer', 'min:0'],
        ];
    }

    // ⭐ مهم جدًا: تحويل JSON من نص إلى Array
    protected function prepareForValidation()
    {
        if (is_string($this->variants)) {
            $this->merge([
                'variants' => json_decode($this->variants, true)
            ]);
        }
    }
}
