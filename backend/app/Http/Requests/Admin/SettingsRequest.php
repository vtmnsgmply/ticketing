<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ApiRequest;

class SettingsRequest extends ApiRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('administrator') ?? false; }

    protected function prepareForValidation(): void
    {
        foreach (['ticket_start_number', 'attachment_max_size_mb', 'customer_reopen_limit', 'smtp_port'] as $field) {
            if ($this->has($field) && $this->input($field) !== '') {
                $this->merge([$field => (int) $this->input($field)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'ticket_prefix' => ['sometimes', 'string', 'max:12', 'regex:/^[A-Za-z0-9-]+$/'],
            'ticket_start_number' => ['sometimes', 'integer', 'min:1'],
            'attachment_max_size_mb' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'notification_web_enabled' => ['sometimes', 'boolean'],
            'notification_email_enabled' => ['sometimes', 'boolean'],
            'allow_customer_reopen_resolved' => ['sometimes', 'boolean'],
            'customer_reopen_limit' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'sender_name' => ['sometimes', 'nullable', 'string', 'max:190'],
            'sender_email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'reply_to_email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'smtp_host' => ['sometimes', 'nullable', 'string', 'max:190'],
            'smtp_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['sometimes', 'nullable', 'string', 'max:190'],
            'smtp_password' => ['sometimes', 'nullable', 'string', 'max:500'],
            'encryption' => ['sometimes', 'nullable', 'in:tls,ssl,none'],
        ];
    }
}
