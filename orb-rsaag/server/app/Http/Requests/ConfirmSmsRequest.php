<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmSmsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'verify_sms_code_id' => ['required', 'numeric',],
            'verify_sms_code' => ['required', 'regex:/^[0-9]{4}$/u']
        ];
    }

    protected function failedValidation(Validator $validator): array
    {
        return ['status' => false, 'errors' => $validator->errors()];
    }
}
