<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GetClientAgreementRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|numeric'
        ];
    }

    protected function failedValidation(Validator $validator): array
    {
        return ['status' => false, 'errors' => 'Неверные параметры'];
    }
}
