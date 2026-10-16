<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|numeric',
            'email' => 'required|email|max:255',
            'blocked' => 'required|boolean',
            'is_admin' => 'required|boolean',
            'is_deleted' => 'required|boolean',
            'email_verified' => 'required|boolean',
        ];
    }
}
