<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GetUserNotificationsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|integer',
            'page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:1'
        ];
    }

    protected function failedValidation(Validator $validator): array
    {
        return ['status' => false, 'errors' => $validator->errors()->messages()];
    }
}
