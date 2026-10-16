<?php

namespace App\Http\Requests\OAuth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetUserCardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ];
    }

    protected function failedValidation(Validator $validator): array
    {
        return ['status' => false, 'errors' => $validator->errors()->messages()];
    }

    public function all($keys = null): array
    {
        $data = parent::all($keys);
        $data['user_id'] = $this->input('user_id');
        return $data;
    }
}
