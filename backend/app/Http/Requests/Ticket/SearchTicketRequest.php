<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\ApiRequest;

class SearchTicketRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:190'],
            'ticket_number' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'exists:priorities,id'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'department' => ['nullable', 'integer', 'exists:departments,id'],
            'assigned_agent' => ['nullable', 'integer', 'exists:users,id'],
            'queue' => ['nullable', 'in:assigned_to_me,unassigned,new,overdue,high_priority,recently_updated,waiting_for_customer,resolved'],
            'sla_state' => ['nullable', 'in:on_track,due_soon,overdue,responded,resolved'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'in:created_at,updated_at,ticket_number,status'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
