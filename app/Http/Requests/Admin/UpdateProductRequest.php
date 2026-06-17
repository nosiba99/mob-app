<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'description'   => ['sometimes', 'string'],
            'price'         => ['sometimes', 'numeric'],
            'category_id'   => ['sometimes', 'exists:categories,id'],

            // الصور
            'main_image'    => ['sometimes', 'image', 'max:4096'],
            'images'        => ['sometimes', 'array'],
            'images.*'      => ['image', 'max:4096'],

            // الفاريانت
            'variants'                          => ['sometimes', 'array'],
            'variants.*.color_id'               => ['required_with:variants', 'exists:colors,id'],
            'variants.*.sizes'                  => ['required_with:variants', 'array'],
            'variants.*.sizes.*.size_id'        => ['required_with:variants', 'exists:sizes,id'],
            'variants.*.sizes.*.stock'          => ['required_with:variants', 'integer', 'min:0'],
        ];
    }
}
