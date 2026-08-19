<?php

namespace App\Http\Requests;

class ManagerDashboardRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('manager') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
