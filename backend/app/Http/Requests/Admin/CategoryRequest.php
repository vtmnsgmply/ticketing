<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class CategoryRequest extends ApiRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('administrator') ?? false; }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'alpha_dash', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
