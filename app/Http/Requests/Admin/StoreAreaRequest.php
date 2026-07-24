<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:100', 'unique:areas,name'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'اسم المنطقة مطلوب',
            'name.unique'    => 'هذه المنطقة موجودة مسبقاً',

            'price.required' => 'سعر التوصيل مطلوب',
            'price.numeric'  => 'السعر يجب أن يكون رقم',
            'price.min'      => 'السعر يجب أن يكون 0 أو أكثر',
        ];
    }
}
