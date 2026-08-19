<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class RoleRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') ?? false;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
