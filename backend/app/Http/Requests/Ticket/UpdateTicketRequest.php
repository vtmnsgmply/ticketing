<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\ApiRequest;

class UpdateTicketRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:20000'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
