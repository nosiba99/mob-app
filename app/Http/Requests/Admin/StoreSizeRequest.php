<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:sizes,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المقاس مطلوب',
            'name.unique'   => 'هذا المقاس موجود مسبقاً',
        ];
    }
}
