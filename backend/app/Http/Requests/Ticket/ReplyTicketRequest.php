<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\ApiRequest;

class ReplyTicketRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'required_without:attachments', 'string', 'max:20000'],
            'reply_to_message_id' => ['nullable', 'integer'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:102400', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm,pdf,doc,docx,xls,xlsx'],
        ];
    }
}
