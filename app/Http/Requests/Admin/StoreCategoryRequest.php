<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // لو POST → required
        // لو PUT/PATCH → sometimes
        $nameRule = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [
                $nameRule,
                'string',
                'max:100',
                'unique:categories,name,' . $this->route('id')
            ],

            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم التصنيف مطلوب',
            'name.unique'   => 'هذا الاسم مستخدم مسبقاً',
            'image.image'   => 'الملف يجب أن يكون صورة',
            'image.max'     => 'حجم الصورة يجب ألا يتجاوز 2MB',
        ];
    }
}
