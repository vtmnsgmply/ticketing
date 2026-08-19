<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class TestEmailRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('administrator') ?? false;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:190'],
        ];
    }
}
