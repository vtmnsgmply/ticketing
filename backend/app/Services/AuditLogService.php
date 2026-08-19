<?php

namespace App\Services;

use App\Models\User;
use App\Repository\AuditLogRepository;
use Illuminate\Http\Request;

class AuditLogService
{
    private const SENSITIVE = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'remember_token',
        'access_token',
        'refresh_token',
        'api_token',
        'authorization',
        'cookie',
        'smtp_password',
        'mail_password',
        'db_password',
        'api_key',
        'private_key',
        'reset_token',
        'verification_token',
    ];

    public function __construct(private readonly AuditLogRepository $auditLogs)
    {
    }

    public function record(?User $actor, string $action, string $entityType, int|string|null $entityId, ?array $old = null, ?array $new = null, ?Request $request = null, ?array $metadata = null): void
    {
        $this->auditLogs->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => is_numeric($entityId) ? (int) $entityId : null,
            'old_values' => $this->mask($old ?? []),
            'new_values' => $this->mask($new ?? []),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $this->mask($metadata ?? []),
        ]);
    }

    private function mask(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($this->isSensitive((string) $key)) {
                $values[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->mask($value);
            }
        }

        return $values;
    }

    private function isSensitive(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE as $sensitive) {
            if (str_contains($normalized, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
