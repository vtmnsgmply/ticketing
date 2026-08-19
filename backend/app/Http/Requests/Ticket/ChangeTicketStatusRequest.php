<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\ApiRequest;
use App\Models\Ticket;
use Illuminate\Validation\Rule;

class ChangeTicketStatusRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['status' => ['required', 'string', Rule::in(Ticket::STATUSES)]];
    }
}
