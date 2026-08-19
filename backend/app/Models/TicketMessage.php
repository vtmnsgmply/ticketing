<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    public const TYPE_CUSTOMER_REPLY = 'customer_reply';
    public const TYPE_AGENT_REPLY = 'agent_reply';
    public const TYPE_INTERNAL_NOTE = 'internal_note';

    public const TYPES = [
        self::TYPE_CUSTOMER_REPLY,
        self::TYPE_AGENT_REPLY,
        self::TYPE_INTERNAL_NOTE,
    ];

    protected $fillable = ['ticket_id', 'user_id', 'message', 'message_type', 'reply_to_message_id', 'deleted_by', 'deleted_at'];

    protected function casts(): array
    {
        return ['deleted_at' => 'datetime'];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(TicketMessageReaction::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(TicketMessageRead::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'reply_to_message_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
