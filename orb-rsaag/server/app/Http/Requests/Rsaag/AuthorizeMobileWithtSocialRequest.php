<?php

namespace App\Http\Requests\Rsaag;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuthorizeMobileWithtSocialRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'social' => ['required', Rule::in(['vk', 'esia', 'fb'])],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'social' => $this->route('social')
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        abort(404);
    }
}
