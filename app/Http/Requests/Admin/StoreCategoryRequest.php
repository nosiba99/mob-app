<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        // لو PUT/PATCH → sometimes، لو POST → required
        $nameRule = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name'  => [$nameRule, 'string', 'max:100', 'unique:categories,name,' . $this->route('category')?->id],
            
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}