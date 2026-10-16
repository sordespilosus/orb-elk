<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SetUserBlockingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|numeric',
            'value_blocking' => 'required|boolean',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'value_blocking' => $this->request->get('value', 0)
        ]);
    }

    protected function failedValidation(Validator $validator): array
    {
        return [
            'errors' => $validator->errors()->messages(),
            'status' => false,
        ];
    }
}
