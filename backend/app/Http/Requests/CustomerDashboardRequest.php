<?php

namespace App\Http\Requests;

class CustomerDashboardRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('customer') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
