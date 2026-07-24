<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'phone'     => ['required', 'string', 'unique:deliveries,phone'],
            'password'  => ['required', 'string', 'min:6'],
            'area_id'   => ['required', 'exists:areas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'اسم المندوب مطلوب',
            'phone.required'    => 'رقم الهاتف مطلوب',
            'phone.unique'      => 'رقم الهاتف مستخدم مسبقاً',

            'password.required' => 'كلمة المرور مطلوبة',
            'password.min'      => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',

            'area_id.required'  => 'المنطقة مطلوبة',
            'area_id.exists'    => 'المنطقة غير موجودة',
        ];
    }
}
