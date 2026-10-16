<?php

namespace App\Http\Requests\Client;

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
            'id' => ['required', 'numeric',]
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        return response()->json(['status' => false, 'errors' => $validator->errors()], 400);
    }
}
