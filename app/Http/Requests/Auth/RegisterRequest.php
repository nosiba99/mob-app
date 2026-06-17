<?php
// app/Http/Requests/Auth/RegisterRequest.php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'      => 'هذا الإيميل مسجّل مسبقاً.',
            'password.confirmed'=> 'كلمة المرور غير متطابقة.',
        ];
    }
}