<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\ApiRequest;

class ReactToTicketMessageRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reaction' => ['required', 'string', 'max:32'],
        ];
    }
}
