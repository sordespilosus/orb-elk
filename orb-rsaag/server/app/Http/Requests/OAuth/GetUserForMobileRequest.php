<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GetUserForMobileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'login' => 'required|string',
            'password' => 'required|string',
            'client_id' => 'required|integer',
            'scope' => 'required|string',
        ];
    }

    public function all($keys = null): array
    {
        $data = parent::all($keys);

        $data['login'] = $this->input('login');
        $data['password'] = $this->input('password');
        $data['client_id'] = $this->input('client_id');
        $data['client_secret'] = $this->input('client_secret');
        $data['scope'] = $this->input('scope', 'personal_data email esia_info');

        return $data;
    }

    protected function failedValidation(Validator $validator): array
    {
        return ['status' => false, 'errors' => ['Invalid parameters']];
    }
}
