<?php

namespace App\Http\Requests;

class TelegramWebhookRequest extends ApiRequest
{
    public function authorize(): bool
    {
        $secret = (string) config('services.telegram.webhook_secret', '');

        return $secret === '' || hash_equals($secret, (string) $this->header('X-Telegram-Bot-Api-Secret-Token', ''));
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'array'],
            'message.chat' => ['nullable', 'array'],
            'message.chat.id' => ['nullable'],
            'message.text' => ['nullable', 'string', 'max:4096'],
        ];
    }
}
