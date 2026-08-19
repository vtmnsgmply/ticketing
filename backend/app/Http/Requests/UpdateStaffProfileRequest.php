<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateStaffProfileRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('agent') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($this->user()?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
