<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:colors,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم اللون مطلوب',
            'name.unique'   => 'هذا اللون موجود مسبقاً',
        ];
    }
}
