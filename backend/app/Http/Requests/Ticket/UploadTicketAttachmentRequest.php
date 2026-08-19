<?php

namespace App\Http\Requests\Ticket;

use App\Http\Requests\ApiRequest;

class UploadTicketAttachmentRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => ['required', 'file', 'max:102400', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm,pdf,doc,docx,xls,xlsx'],
        ];
    }
}
