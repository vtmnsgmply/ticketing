<?php

namespace App\Http\Requests;

class StaffDashboardRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('agent') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
