<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\ApiRequest;

class AssignTicketRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['assigned_agent_id' => ['required', 'integer', 'exists:users,id']];
    }
}
