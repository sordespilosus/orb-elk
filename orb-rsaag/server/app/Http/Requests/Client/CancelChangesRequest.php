<?php

namespace App\Http\Requests\Client;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelChangesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'numeric', Rule::exists('oauth_clients', 'id')],
            'changes_ids' => 'required|array',
            'changes_ids.*' => ['required', 'integer', Rule::exists('auth.clients.oauth_clients_changes', 'id')],
        ];
    }

    protected function failedValidation(Validator $validator): array
    {
        return ['status' => false, 'errors' => $validator->errors()->messages()];
    }
}
