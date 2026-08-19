<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class StatusRequest extends ApiRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('administrator') ?? false; }

    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
