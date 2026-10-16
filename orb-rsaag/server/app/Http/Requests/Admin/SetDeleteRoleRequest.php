<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SetDeleteRoleRequest extends FormRequest
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
        ];
    }

    protected function failedValidation(Validator $validator): array
    {
        return [
            'errors' => $validator->errors()->messages(),
            'status' => false,
        ];
    }

}
