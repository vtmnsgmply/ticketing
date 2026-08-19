<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\SlaRule;
use App\Repository\AdminConfigRepository;
use App\Support\AuditAction;

class AdminConfigService
{
    public function __construct(
        private readonly AdminConfigRepository $config,
        private readonly AuditLogService $audit,
    ) {
    }

    public function roles() { return $this->config->roles(); }
    public function departments(array $filters) { return $this->config->departments($filters); }
    public function categories(array $filters) { return $this->config->categories($filters); }
    public function priorities() { return $this->config->priorities(); }
    public function slaRules() { return $this->config->slaRules(); }

    public function saveDepartment($actor, array $data, $request, ?Department $department = null): Department
    {
        $old = $department?->toArray();
        $department = $this->config->saveDepartment($data, $department);
        $action = $old ? AuditAction::DEPARTMENT_UPDATED : AuditAction::DEPARTMENT_CREATED;
        if ($old && array_key_exists('is_active', $data) && (bool) $old['is_active'] !== (bool) $data['is_active']) {
            $action = (bool) $data['is_active'] ? AuditAction::DEPARTMENT_ACTIVATED : AuditAction::DEPARTMENT_DISABLED;
        }
        $this->audit->record($actor, $action, 'department', $department->id, $old, $data, $request);
        TicketCatalogService::clearOptionsCache();
        return $department;
    }

    public function saveCategory($actor, array $data, $request, ?Category $category = null): Category
    {
        $old = $category?->toArray();
        $category = $this->config->saveCategory($data, $category);
        $action = $old ? AuditAction::CATEGORY_UPDATED : AuditAction::CATEGORY_CREATED;
        if ($old && array_key_exists('is_active', $data) && (bool) $old['is_active'] !== (bool) $data['is_active']) {
            $action = (bool) $data['is_active'] ? AuditAction::CATEGORY_ACTIVATED : AuditAction::CATEGORY_DISABLED;
        }
        $this->audit->record($actor, $action, 'category', $category->id, $old, $data, $request);
        TicketCatalogService::clearOptionsCache();
        return $category;
    }

    public function updatePriority($actor, Priority $priority, array $data, $request): Priority
    {
        $old = $priority->toArray();
        $priority = $this->config->updatePriority($priority, $data);
        $action = AuditAction::PRIORITY_UPDATED;
        if (array_key_exists('is_active', $data) && (bool) $old['is_active'] !== (bool) $data['is_active']) {
            $action = (bool) $data['is_active'] ? AuditAction::PRIORITY_ACTIVATED : AuditAction::PRIORITY_DISABLED;
        }
        $this->audit->record($actor, $action, 'priority', $priority->id, $old, $data, $request);
        TicketCatalogService::clearOptionsCache();
        return $priority;
    }

    public function priorityStatus($actor, Priority $priority, bool $active, $request): Priority
    {
        return $this->updatePriority($actor, $priority, $priority->only(['name', 'sort_order']) + ['is_active' => $active], $request);
    }

    public function updateSla($actor, SlaRule $rule, array $data, $request): SlaRule
    {
        $old = $rule->toArray();
        $rule = $this->config->updateSla($rule, $data);
        $this->audit->record($actor, AuditAction::SLA_UPDATED, 'sla_rule', $rule->id, $old, $data, $request);
        return $rule;
    }
}
