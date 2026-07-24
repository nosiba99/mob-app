<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'الاسم الأول مطلوب',
            'last_name.required'  => 'اسم العائلة مطلوب',
            'email.required'      => 'الإيميل مطلوب',
            'email.email'         => 'صيغة الإيميل غير صحيحة',
            'email.unique'        => 'هذا الإيميل مسجّل مسبقاً.',
            'password.required'   => 'كلمة المرور مطلوبة',
            'password.min'        => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed'  => 'كلمة المرور غير متطابقة.',
        ];
    }
}
