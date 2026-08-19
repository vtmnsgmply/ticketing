<?php

namespace App\Http\Requests;

class StaffNotificationRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('agent') ?? false;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
