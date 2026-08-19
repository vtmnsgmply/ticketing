<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class AuditLogListRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:190'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:150'],
            'entity_type' => ['nullable', 'string', 'max:150'],
            'entity_id' => ['nullable', 'integer'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', Rule::in(['created_at', 'action', 'entity_type', 'ip_address'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
