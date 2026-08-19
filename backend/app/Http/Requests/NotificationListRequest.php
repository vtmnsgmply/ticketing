<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class NotificationListRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_read' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'channel' => ['nullable', Rule::in(['web', 'email'])],
        ];
    }
}
