<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Repository\AdminConfigRepository;
use App\Support\AuditAction;

class SettingsService
{
    public function __construct(
        private readonly AdminConfigRepository $config,
        private readonly AuditLogService $audit,
    ) {
    }

    public function system(): array
    {
        return $this->config->settings()->reject(fn ($s) => str_contains($s->key, 'smtp_'))->values()->all();
    }

    public function email(): array
    {
        return $this->config->settings()
            ->filter(fn ($s) => str_starts_with($s->key, 'smtp_') || in_array($s->key, ['sender_name', 'sender_email', 'reply_to_email', 'encryption'], true))
            ->map(function ($setting) {
                if ($setting->value_type === 'secret') {
                    $setting->value = $setting->value ? '[configured]' : null;
                }
                return $setting;
            })
            ->values()
            ->all();
    }

    public function update($actor, array $settings, $request, bool $email = false): array
    {
        if (! $email && isset($settings['ticket_start_number'])) {
            $currentSequence = $this->config->currentTicketSequence();
            if ($currentSequence > 0 && (int) $settings['ticket_start_number'] <= $currentSequence) {
                throw new BusinessRuleException('Ticket start number cannot move backward into an existing ticket range.');
            }
        }

        foreach ($settings as $key => $value) {
            $type = str_contains($key, 'password') ? 'secret' : (is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string'));
            if ($type === 'secret' && ($value === null || $value === '')) {
                continue;
            }
            $this->config->upsertSetting($key, $value, $type, ! str_contains($key, 'password'));
        }
        $this->audit->record($actor, $email ? AuditAction::SETTINGS_EMAIL_UPDATED : AuditAction::SETTINGS_SYSTEM_UPDATED, 'system_settings', null, null, $settings, $request);
        return $email ? $this->email() : $this->system();
    }
}
