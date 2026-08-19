<?php

namespace App\Repository;

use App\Models\TicketAttachment;

class TicketAttachmentRepository
{
    public function findById(int $attachmentId): ?TicketAttachment
    {
        return TicketAttachment::query()->with('ticket', 'uploader.role')->find($attachmentId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): TicketAttachment
    {
        return TicketAttachment::query()->create($data);
    }
}
