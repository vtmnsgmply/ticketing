<?php

namespace App\Repository;

use App\Models\BlockedWord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class BlockedWordRepository
{
    private const CACHE_KEY = 'conversation_moderation.active_blocked_words';

    public function getActiveWords()
    {
        return Cache::remember(self::CACHE_KEY, 300, fn () => BlockedWord::query()
            ->with(['variants' => fn ($query) => $query->where('is_active', true)])
            ->where('is_active', true)
            ->orderByDesc('severity')
            ->get());
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return BlockedWord::query()
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('word', 'like', "%{$search}%"))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when(($filters['is_active'] ?? '') !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->latest()
            ->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    public function find(int $id): ?BlockedWord
    {
        return BlockedWord::query()->find($id);
    }

    public function create(array $data): BlockedWord
    {
        $word = BlockedWord::query()->create($data);
        $this->forgetCache();

        return $word;
    }

    public function update(BlockedWord $word, array $data): BlockedWord
    {
        $word->update($data);
        $this->forgetCache();

        return $word->fresh();
    }

    public function setActive(BlockedWord $word, bool $active, ?int $actorId = null): BlockedWord
    {
        $word->update(['is_active' => $active, 'updated_by' => $actorId]);
        $this->forgetCache();

        return $word->fresh();
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
