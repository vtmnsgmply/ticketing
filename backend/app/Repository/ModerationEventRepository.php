<?php

namespace App\Repository;

use App\Models\ConversationModerationEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ModerationEventRepository
{
    public function create(array $data): ConversationModerationEvent
    {
        return ConversationModerationEvent::query()->create($data)->load(['ticket', 'user.role', 'reviewer.role']);
    }

    public function findForUser(int $id, User $user): ?ConversationModerationEvent
    {
        $query = ConversationModerationEvent::query()->with(['ticket.department', 'user.role', 'reviewer.role']);

        if ($user->hasRole(Role::ADMINISTRATOR)) {
            return $query->find($id);
        }

        if ($user->hasRole(Role::MANAGER)) {
            $departmentIds = $user->departments()->pluck('departments.id')->all();
            if ($user->primary_department_id !== null) {
                $departmentIds[] = (int) $user->primary_department_id;
            }
            $departmentIds = array_values(array_unique(array_filter($departmentIds)));

            return $query->whereHas('ticket', fn ($ticket) => $ticket
                ->whereIn('department_id', $departmentIds)
                ->orWhereNull('department_id'))
                ->find($id);
        }

        return null;
    }

    public function paginateForReview(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = ConversationModerationEvent::query()->with(['ticket', 'user.role', 'reviewer.role']);

        if ($user->hasRole(Role::MANAGER)) {
            $departmentIds = $user->departments()->pluck('departments.id')->all();
            if ($user->primary_department_id !== null) {
                $departmentIds[] = (int) $user->primary_department_id;
            }
            $departmentIds = array_values(array_unique(array_filter($departmentIds)));
            $query->whereHas('ticket', fn ($ticket) => $ticket->whereIn('department_id', $departmentIds)->orWhereNull('department_id'));
        } elseif (! $user->hasRole(Role::ADMINISTRATOR)) {
            $query->whereRaw('1 = 0');
        }

        $query
            ->when($filters['severity'] ?? null, fn ($builder, $severity) => $builder->where('severity', $severity))
            ->when($filters['action'] ?? null, fn ($builder, $action) => $builder->where('action', $action))
            ->when($filters['review_status'] ?? null, fn ($builder, $status) => $builder->where('review_status', $status));

        return $query->latest()->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    public function updateReview(ConversationModerationEvent $event, User $reviewer, string $status): ConversationModerationEvent
    {
        $event->update([
            'review_status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $event->fresh(['ticket', 'user.role', 'reviewer.role']);
    }
}
