<?php

namespace App\Repository;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Role;
use App\Models\SlaRule;
use App\Models\SystemSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminConfigRepository
{
    public function departments(array $filters = []): LengthAwarePaginator
    {
        return Department::query()->withCount('users')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest()
            ->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    public function categories(array $filters = []): LengthAwarePaginator
    {
        return Category::query()->with('department')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->latest()
            ->paginate(min(max((int) ($filters['per_page'] ?? 20), 1), 100));
    }

    public function priorities()
    {
        return Priority::query()->with('slaRule')->orderBy('sort_order')->get();
    }

    public function roles()
    {
        return Role::query()->withCount('users')->orderBy('name')->get();
    }

    public function slaRules()
    {
        return SlaRule::query()->with('priority')->get();
    }

    public function settings()
    {
        return SystemSetting::query()->orderBy('key')->get();
    }

    public function settingValue(string $key): ?string
    {
        return SystemSetting::query()->where('key', $key)->value('value');
    }

    public function currentTicketSequence(): int
    {
        return (int) (DB::table('ticket_number_sequences')->where('id', 1)->value('current_value') ?? 0);
    }

    public function upsertSetting(string $key, mixed $value, string $type = 'string', bool $public = false): SystemSetting
    {
        return SystemSetting::query()->updateOrCreate(['key' => $key], [
            'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
            'value_type' => $type,
            'is_public' => $public,
        ]);
    }

    public function saveDepartment(array $data, ?Department $department = null): Department
    {
        if ($department) {
            $department->update($data);

            return $department->fresh(['users']);
        }

        return Department::query()->create($data)->load('users');
    }

    public function saveCategory(array $data, ?Category $category = null): Category
    {
        if ($category) {
            $category->update($data);

            return $category->fresh('department');
        }

        return Category::query()->create($data)->load('department');
    }

    public function updatePriority(Priority $priority, array $data): Priority
    {
        $priority->update($data);

        return $priority->fresh('slaRule');
    }

    public function updateSla(SlaRule $rule, array $data): SlaRule
    {
        $rule->update($data);

        return $rule->fresh('priority');
    }
}
