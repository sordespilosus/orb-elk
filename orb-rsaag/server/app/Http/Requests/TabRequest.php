<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TabRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'tab' => ['nullable', Rule::in(['clients', 'changes'])]
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
