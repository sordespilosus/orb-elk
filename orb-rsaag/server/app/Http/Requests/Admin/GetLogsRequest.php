<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetLogsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'page' => 'required|numeric',
            'per_page' => 'required|numeric',
            'order_by' => [
                'required',
                Rule::in(['id', 'user_id', 'date_created', 'method', 'url'])
            ],
            'is_asc' => ['nullable', Rule::in(['true', 'false'])],
            'max_id' => ['nullable', 'numeric'],
            'min_id' => ['nullable', 'numeric'],
            'max_date' => ['nullable', 'date'],
            'min_date' => ['nullable', 'date'],
            'method_get' => ['nullable', Rule::in(['true', 'false'])],
            'method_post' => ['nullable', Rule::in(['true', 'false'])],
            'method_others' => ['nullable', Rule::in(['true', 'false'])],
            'ip' => ['nullable', 'string']
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        return [
            'errors' => $validator->errors()->messages(),
            'status' => false,
        ];
    }
}
