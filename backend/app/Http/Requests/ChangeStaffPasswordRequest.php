<?php

namespace App\Http\Requests;

class ChangeStaffPasswordRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('agent') ?? false;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ];
    }
}
