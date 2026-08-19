<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ReportFilterRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'assigned_agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['new', 'open', 'assigned', 'in_progress', 'waiting_for_customer', 'resolved', 'closed', 'cancelled'])],
        ];
    }
}
