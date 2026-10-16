<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetRolesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'order_by' => ['nullable', Rule::in(['id', 'name', 'public_name'])],
            'is_asc' => ['nullable', 'boolean'],
        ];
    }
}
