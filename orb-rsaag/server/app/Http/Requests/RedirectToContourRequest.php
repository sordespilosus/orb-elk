<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RedirectToContourRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'redirect_url' => ['required', 'url'],
            'contour' => ['required', Rule::in(['cabinet-test', 'test'])]
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'contour' => $this->route('contour')
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        abort(404);
    }
}
