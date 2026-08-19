<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\ApiRequest;
use App\Models\Role;

class CreateTicketRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'customer_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:102400', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm,pdf,doc,docx,xls,xlsx'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->hasRole(Role::CUSTOMER)) {
            $this->merge(['customer_id' => null]);
        }
    }
}
