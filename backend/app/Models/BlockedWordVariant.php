<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class BlockedWordVariant extends Model
{
    public const TYPES = ['canonical', 'common_misspelling', 'compressed', 'morphological', 'manual'];

    protected $fillable = ['blocked_word_id', 'variant', 'normalized_variant', 'variant_type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('conversation_moderation.active_blocked_words'));
        static::deleted(fn () => Cache::forget('conversation_moderation.active_blocked_words'));
    }

    public function blockedWord(): BelongsTo
    {
        return $this->belongsTo(BlockedWord::class);
    }
}
