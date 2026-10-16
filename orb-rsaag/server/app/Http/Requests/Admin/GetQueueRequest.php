<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetQueueRequest extends FormRequest
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
            'order_by' => ['required', Rule::in(['id', 'email', 'status'])],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'page' => $this->request->get('page', 1),
            'per_page' => $this->request->get('per_page', 50),
            'order_by' => $this->request->get('order_by', 'id'),
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
