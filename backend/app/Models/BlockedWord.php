<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class BlockedWord extends Model
{
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];
    public const ACTIONS = ['mask', 'block', 'flag', 'mask_flag'];

    protected $fillable = ['word', 'normalized_word', 'severity', 'action', 'is_active', 'notes', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('conversation_moderation.active_blocked_words'));
        static::deleted(fn () => Cache::forget('conversation_moderation.active_blocked_words'));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(BlockedWordVariant::class);
    }
}
