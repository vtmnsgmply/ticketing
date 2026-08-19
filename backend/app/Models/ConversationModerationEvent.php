<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationModerationEvent extends Model
{
    public const REVIEW_STATUSES = ['not_required', 'pending', 'reviewed', 'dismissed', 'escalated'];

    protected $fillable = [
        'ticket_id',
        'ticket_message_id',
        'user_id',
        'user_role',
        'action',
        'severity',
        'matched_terms',
        'original_content_encrypted',
        'filtered_content',
        'review_status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_terms' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
