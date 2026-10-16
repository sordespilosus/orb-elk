<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetUsersRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'page' => 'required|numeric',
            'per_page' => 'required|numeric',
            'order_by' => [
                'required',
                Rule::in(['id', 'email', 'fio', 'blocked', 'email_verified_at'])
            ],
            'is_asc' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'page' => $this->request->get('page', 1),
            'per_page' => $this->request->get('per_page', 50),
            'order_by' => $this->request->get('order_by', 'id'),
            'is_asc' => $this->request->get('is_asc', true)
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
