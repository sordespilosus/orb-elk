<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SendMailingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'subject' => 'required|string',
            'description' => 'required|string',
        ];
    }

    protected function failedValidation(Validator $validator): array
    {
        return [
            'status' => $validator->errors()->messages(),
            'status_id' => 0,
        ];
    }
}
