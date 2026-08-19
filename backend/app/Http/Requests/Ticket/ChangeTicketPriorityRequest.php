<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\ApiRequest;

class ChangeTicketPriorityRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['priority_id' => ['required', 'integer', 'exists:priorities,id']];
    }
}
