<?php

namespace App\Repository;

use App\Models\BlockedWord;
use App\Models\BlockedWordVariant;
use Illuminate\Support\Facades\Cache;

class BlockedWordVariantRepository
{
    public function create(BlockedWord $word, array $data): BlockedWordVariant
    {
        $variant = $word->variants()->create($data);
        $this->forgetCache();

        return $variant;
    }

    public function update(BlockedWordVariant $variant, array $data): BlockedWordVariant
    {
        $variant->update($data);
        $this->forgetCache();

        return $variant->fresh();
    }

    public function setActive(BlockedWordVariant $variant, bool $active): BlockedWordVariant
    {
        $variant->update(['is_active' => $active]);
        $this->forgetCache();

        return $variant->fresh();
    }

    public function delete(BlockedWordVariant $variant): void
    {
        $variant->delete();
        $this->forgetCache();
    }

    public function forgetCache(): void
    {
        Cache::forget('conversation_moderation.active_blocked_words');
    }
}
