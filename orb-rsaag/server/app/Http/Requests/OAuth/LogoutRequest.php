<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Rule;

class LogoutRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('oauth_clients', 'id')],
            'redirect_uri' => 'required|url',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = Lang::get('errors.60');
        if ($errors == 'errors.60') {
            $errors = Lang::get('errors.500');
        }
        return response()->view("errors/error", [
            'errors' => [$errors],
        ], 400);
    }
}
