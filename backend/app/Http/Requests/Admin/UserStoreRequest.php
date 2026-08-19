<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends ApiRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('administrator') ?? false; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:190'],
            'customer_label' => ['nullable', 'string', Rule::in(['priority', 'vip', 'watchlist', 'at_risk'])],
            'telegram_profile' => ['nullable', 'regex:/^-?\d+$/', 'max:190'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'primary_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'department_ids' => ['sometimes', 'array'],
            'department_ids.*' => ['integer', 'distinct', 'exists:departments,id'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
