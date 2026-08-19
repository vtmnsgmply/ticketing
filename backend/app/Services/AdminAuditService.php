<?php

namespace App\Services;

use App\Repository\AuditLogRepository;
use App\Support\AuditAction;

class AdminAuditService
{
    public function __construct(private readonly AuditLogRepository $auditLogs)
    {
    }

    public function paginate(array $filters)
    {
        return $this->auditLogs->paginate($filters);
    }

    public function find(int $id)
    {
        return $this->auditLogs->find($id);
    }

    public function actions(): array
    {
        return array_values(array_unique(array_merge(AuditAction::all(), $this->auditLogs->actions())));
    }

    public function entityTypes(): array
    {
        return $this->auditLogs->entityTypes();
    }
}
