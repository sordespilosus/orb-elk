<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class GetNotificationPrintRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'service_number' => 'required|string',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'service_number' => $this->route('id')
        ]);
    }

    protected function failedValidation(Validator $validator): array
    {
        return ['status' => false];
    }
}
