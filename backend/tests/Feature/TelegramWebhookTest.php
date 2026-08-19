<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
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
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.webhook_secret' => 'secret',
        ]);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

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
            ->assertJsonPath('data.handled', true)
            ->assertJsonPath('data.linked', false);
    }

    public function test_plain_text_from_linked_customer_is_added_to_single_active_ticket(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
        $customer = User::factory()->create(['telegram_profile' => '123456789']);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123456789, 'type' => 'private'],
                'text' => 'I still need help with this.',
            ],
        ])->assertOk()
            ->assertJsonPath('data.handled', true)
            ->assertJsonPath('data.linked', true)
            ->assertJsonPath('data.posted', true)
            ->assertJsonPath('data.ticket_id', $ticket->id);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'I still need help with this.',
            'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
        ]);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'action' => 'customer_replied',
        ]);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/bottest-token/sendMessage')
            && $request['chat_id'] === '123456789'
            && $request['text'] === "Your message was added to ticket {$ticket->ticket_number}.");
    }

    public function test_plain_text_with_multiple_active_tickets_prompts_for_ticket_number(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
        $customer = User::factory()->create(['telegram_profile' => '123456789']);
        $first = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'ticket_number' => 'TKT-11001',
            'subject' => 'First active ticket',
            'status' => Ticket::STATUS_OPEN,
        ]);
        $second = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'ticket_number' => 'TKT-11002',
            'subject' => 'Second active ticket',
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123456789, 'type' => 'private'],
                'text' => 'Please check this.',
            ],
        ])->assertOk()
            ->assertJsonPath('data.handled', true)
            ->assertJsonPath('data.linked', true)
            ->assertJsonPath('data.posted', false);

        $this->assertDatabaseMissing('ticket_messages', [
            'user_id' => $customer->id,
            'message' => 'Please check this.',
        ]);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'You have multiple active tickets')
            && str_contains($request['text'], $first->ticket_number)
            && str_contains($request['text'], $second->ticket_number));
    }

    public function test_ticket_command_routes_customer_message_to_matching_active_ticket(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
        $customer = User::factory()->create(['telegram_profile' => '123456789']);
        $first = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'ticket_number' => 'TKT-12001',
            'status' => Ticket::STATUS_OPEN,
        ]);
        $second = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'ticket_number' => 'TKT-12002',
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123456789, 'type' => 'private'],
                'text' => '/ticket TKT-12002 This belongs on the second ticket.',
            ],
        ])->assertOk()
            ->assertJsonPath('data.posted', true)
            ->assertJsonPath('data.ticket_id', $second->id)
            ->assertJsonPath('data.ticket_number', 'TKT-12002');

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $first->id,
            'message' => 'This belongs on the second ticket.',
        ]);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $second->id,
            'user_id' => $customer->id,
            'message' => 'This belongs on the second ticket.',
            'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
        ]);
    }
}
