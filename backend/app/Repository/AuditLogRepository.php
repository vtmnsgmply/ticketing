<?php

namespace App\Repository;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository
{
    public function create(array $data): AuditLog
    {
        return AuditLog::query()->create($data + ['created_at' => now()]);
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        return AuditLog::query()->with('user.role')
            ->when($filters['search'] ?? null, function ($q, $s): void {
                $q->where(function ($query) use ($s): void {
                    $query->where('action', 'like', "%{$s}%")
                        ->orWhere('entity_type', 'like', "%{$s}%")
                        ->orWhere('ip_address', 'like', "%{$s}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
                });
            })
            ->when($filters['user_id'] ?? null, fn ($q, $s) => $q->where('user_id', $s))
            ->when($filters['action'] ?? null, fn ($q, $s) => $q->where('action', $s))
            ->when($filters['entity_type'] ?? null, fn ($q, $s) => $q->where('entity_type', $s))
            ->when($filters['entity_id'] ?? null, fn ($q, $s) => $q->where('entity_id', $s))
            ->when($filters['ip_address'] ?? null, fn ($q, $s) => $q->where('ip_address', $s))
            ->when($filters['date_from'] ?? null, fn ($q, $s) => $q->whereDate('created_at', '>=', $s))
            ->when($filters['date_to'] ?? null, fn ($q, $s) => $q->whereDate('created_at', '<=', $s))
            ->orderBy(in_array($filters['sort'] ?? '', ['created_at', 'action', 'entity_type', 'ip_address'], true) ? $filters['sort'] : 'created_at', ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc')
            ->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    public function find(int $id): ?AuditLog
    {
        return AuditLog::query()->with('user.role')->find($id);
    }

    public function actions(): array
    {
        return AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action')->all();
    }

    public function entityTypes(): array
    {
        return AuditLog::query()->whereNotNull('entity_type')->select('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type')->all();
    }
}
