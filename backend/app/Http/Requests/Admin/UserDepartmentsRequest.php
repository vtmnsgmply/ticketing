<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class UserDepartmentsRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') ?? false;
    }

    public function rules(): array
    {
        return [
            'primary_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'department_ids' => ['array'],
            'department_ids.*' => ['integer', 'distinct', 'exists:departments,id'],
        ];
    }
}
