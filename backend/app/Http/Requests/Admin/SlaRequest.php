<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class SlaRequest extends ApiRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('administrator') ?? false; }

    public function rules(): array
    {
        return [
            'first_response_minutes' => ['required', 'integer', 'min:1'],
            'resolution_minutes' => ['required', 'integer', 'min:1', 'gte:first_response_minutes'],
            'pause_on_waiting_customer' => ['required', 'boolean'],
            'use_business_hours' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
