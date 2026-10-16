<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SetEditRoleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'numeric'],
            'role_name' => 'required|string|max:255',
            'role_tech_name' => ['required', 'string', 'regex:/^[a-zA-Z_0-9]*$/u', 'max:255'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        return [
            'errors' => $validator->errors()->messages(),
            'status' => false,
        ];
    }
}
