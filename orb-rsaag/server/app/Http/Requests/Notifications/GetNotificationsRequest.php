<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetNotificationsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'page' => 'required|numeric',
            'per_page' => 'required|numeric',
            'status' => 'nullable|numeric',
            'service' => 'nullable|numeric',
            'org_id' => 'nullable|numeric',
            'order_by' => ['required', Rule::in(['show_number'])],
            'is_asc' => ['nullable', 'boolean']
        ];
    }

    public function all($keys = null)
    {
        $data = parent::all($keys);
        $data['page'] = $this->input('page', 1);
        $data['per_page'] = $this->input('per_page', 10000);
        $data['org_id'] = $this->input('org_id', null);
        $data['status'] = $this->input('status', null);
        $data['service'] = $this->input('service', null);
        $data['order_by'] = $this->input('order_by', 'show_number');
        $data['is_asc'] = $this->input('is_asc', false);

        return $data;
    }

    protected function failedValidation(Validator $validator): array
    {
        return ['status' => false];
    }
}
