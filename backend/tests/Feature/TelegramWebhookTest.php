<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_command_replies_with_chat_id(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123456789, 'type' => 'private'],
                'text' => '/start hi',
            ],
        ])->assertOk()
            ->assertJsonPath('data.handled', true)
            ->assertJsonPath('data.chat_id', '123456789');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/bottest-token/sendMessage')
            && $request['chat_id'] === '123456789'
            && str_contains($request['text'], 'Your Telegram Chat ID is 123456789'));
    }

    public function test_webhook_secret_header_is_required_when_configured(): void
    {
        config(['services.telegram.webhook_secret' => 'secret']);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123456789],
                'text' => '/start',
            ],
        ])->assertForbidden();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret')
            ->postJson('/api/telegram/webhook', [
                'message' => [
                    'chat' => ['id' => 123456789],
                    'text' => 'hello',
                ],
            ])->assertOk()
            ->assertJsonPath('data.handled', false);
    }
}
