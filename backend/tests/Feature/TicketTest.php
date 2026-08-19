<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\BlockedWord;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Role;
use App\Models\SlaRule;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketMessageReaction;
use App\Models\User;
use App\Events\TicketConversationUpdated;
use App\Support\AuditAction;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private Category $category;
    private Priority $priority;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->department = Department::factory()->create();
        $this->category = Category::factory()->create(['department_id' => $this->department->id]);
        $this->priority = Priority::factory()->create(['slug' => 'medium', 'sort_order' => 20]);
        SlaRule::query()->create([
            'priority_id' => $this->priority->id,
            'first_response_minutes' => 240,
            'resolution_minutes' => 1440,
            'is_active' => true,
        ]);
    }

    public function test_customer_can_create_ticket_with_sla_and_activity(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson('/api/tickets', [
                'subject' => 'Cannot access portal',
                'description' => 'The customer portal returns an error.',
                'department_id' => $this->department->id,
                'category_id' => $this->category->id,
                'priority_id' => $this->priority->id,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ticket.status', Ticket::STATUS_NEW)
            ->assertJsonPath('data.ticket.customer.id', $customer->id);

        $this->assertDatabaseHas('tickets', ['subject' => 'Cannot access portal']);
        $ticket = Ticket::query()->firstOrFail();
        $this->assertStringStartsWith('TKT-', $ticket->ticket_number);
        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => 'ticket_created']);
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'ticket_id' => $ticket->id, 'type' => 'ticket_created']);
    }

    public function test_ticket_creation_validation_rejects_invalid_fields(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson('/api/tickets', ['priority_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'description', 'priority_id']);
    }

    public function test_customer_can_view_own_ticket_without_internal_notes(): void
    {
        $owner = $this->userWithRole(Role::CUSTOMER);
        $agent = $this->userWithRole(Role::AGENT);
        $ticket = Ticket::query()->create([
            'ticket_number' => 'TKT-20001',
            'customer_id' => $owner->id,
            'subject' => 'Private ticket',
            'description' => 'Owned by customer',
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_NEW,
        ]);
        TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'message' => 'Staff-only context',
            'message_type' => TicketMessage::TYPE_INTERNAL_NOTE,
        ]);

        $this->withToken($owner->createToken('test')->plainTextToken)
            ->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonMissing(['message' => 'Staff-only context']);
    }

    public function test_customer_cannot_view_another_customers_ticket(): void
    {
        $owner = $this->userWithRole(Role::CUSTOMER);
        $other = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::query()->create([
            'ticket_number' => 'TKT-20002',
            'customer_id' => $owner->id,
            'subject' => 'Private ticket',
            'description' => 'Owned by customer',
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_NEW,
        ]);

        $this->withToken($other->createToken('test')->plainTextToken)
            ->getJson("/api/tickets/{$ticket->id}")
            ->assertStatus(404);
    }

    public function test_manager_can_assign_agent_and_activity_is_recorded(): void
    {
        $manager = $this->userWithRole(Role::MANAGER);
        $manager->forceFill(['primary_department_id' => $this->department->id])->save();
        $agent = $this->userWithRole(Role::AGENT);
        $agent->forceFill(['primary_department_id' => $this->department->id])->save();
        $agent->departments()->sync([$this->department->id]);
        $ticket = Ticket::factory()->create([
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
        ]);

        $this->withToken($manager->createToken('test')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/assign", ['assigned_agent_id' => $agent->id])
            ->assertOk()
            ->assertJsonPath('data.ticket.assigned_agent.id', $agent->id);

        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => 'ticket_assigned']);
    }

    public function test_customer_cannot_assign_ticket(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $agent = $this->userWithRole(Role::AGENT);
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'priority_id' => $this->priority->id]);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/assign", ['assigned_agent_id' => $agent->id])
            ->assertForbidden();
    }

    public function test_valid_status_transition_sets_resolution_timestamp(): void
    {
        $agent = $this->userWithRole(Role::AGENT);
        $customer = $this->userWithRole(Role::CUSTOMER);
        $customer->forceFill(['telegram_profile' => '123456789'])->save();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'assigned_agent_id' => $agent->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
            'priority_id' => $this->priority->id,
        ]);
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $this->withToken($agent->createToken('test')->plainTextToken)
            ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => Ticket::STATUS_RESOLVED])
            ->assertOk()
            ->assertJsonPath('data.ticket.status', Ticket::STATUS_RESOLVED);

        $this->assertNotNull($ticket->fresh()->resolved_at);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/bottest-token/sendMessage')
            && $request['chat_id'] === '123456789'
            && $request['text'] === "Your ticket {$ticket->ticket_number} status is Resolved.");
    }

    public function test_status_transition_skips_telegram_when_customer_profile_is_missing(): void
    {
        $agent = $this->userWithRole(Role::AGENT);
        $customer = $this->userWithRole(Role::CUSTOMER);
        $customer->forceFill(['telegram_profile' => null])->save();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'assigned_agent_id' => $agent->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
            'priority_id' => $this->priority->id,
        ]);
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $this->withToken($agent->createToken('test')->plainTextToken)
            ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => Ticket::STATUS_RESOLVED])
            ->assertOk();

        Http::assertNothingSent();
    }

    public function test_staff_roles_can_resolve_active_tickets_directly(): void
    {
        foreach ([Role::AGENT, Role::MANAGER, Role::ADMINISTRATOR] as $role) {
            $user = $this->userWithRole($role);

            if ($role !== Role::ADMINISTRATOR) {
                $user->forceFill(['primary_department_id' => $this->department->id])->save();
                $user->departments()->sync([$this->department->id]);
            }

            foreach ([Ticket::STATUS_NEW, Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED] as $status) {
                $ticket = Ticket::factory()->create([
                    'assigned_agent_id' => $role === Role::AGENT ? $user->id : null,
                    'department_id' => $this->department->id,
                    'category_id' => $this->category->id,
                    'priority_id' => $this->priority->id,
                    'status' => $status,
                ]);

                $this->withToken($user->createToken("test-{$role}-{$status}")->plainTextToken)
                    ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => Ticket::STATUS_RESOLVED])
                    ->assertOk()
                    ->assertJsonPath('data.ticket.status', Ticket::STATUS_RESOLVED);

                $this->assertNotNull($ticket->fresh()->resolved_at);
            }
        }
    }

    public function test_invalid_status_transition_fails(): void
    {
        $agent = $this->userWithRole(Role::AGENT);
        $ticket = Ticket::factory()->create([
            'assigned_agent_id' => $agent->id,
            'status' => Ticket::STATUS_NEW,
            'priority_id' => $this->priority->id,
        ]);

        $this->withToken($agent->createToken('test')->plainTextToken)
            ->patchJson("/api/tickets/{$ticket->id}/status", ['status' => Ticket::STATUS_CLOSED])
            ->assertForbidden();
    }

    public function test_customer_can_cancel_own_active_ticket_and_admin_is_notified(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.ticket.status', Ticket::STATUS_CANCELLED);

        $this->assertNotNull($ticket->fresh()->cancelled_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_cancelled',
        ]);
    }

    public function test_agent_reply_sets_first_response_but_internal_note_does_not(): void
    {
        $agent = $this->userWithRole(Role::AGENT);
        $ticket = Ticket::factory()->create([
            'assigned_agent_id' => $agent->id,
            'priority_id' => $this->priority->id,
        ]);

        $this->withToken($agent->createToken('test')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/internal-note", ['message' => 'Investigating privately'])
            ->assertOk();

        $this->assertNull($ticket->fresh()->first_responded_at);

        $this->withToken($agent->createToken('test')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/reply", ['message' => 'We are checking this now.'])
            ->assertOk();

        $this->assertNotNull($ticket->fresh()->first_responded_at);
    }

    public function test_allowed_attachment_upload_succeeds(): void
    {
        Storage::fake('local');
        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'priority_id' => $this->priority->id]);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->post("/api/tickets/{$ticket->id}/attachments", [
                'attachments' => [UploadedFile::fake()->image('screen.png')],
            ])
            ->assertOk();

        $this->assertDatabaseHas('ticket_attachments', ['ticket_id' => $ticket->id, 'original_name' => 'screen.png']);
    }

    public function test_conversation_supports_media_reactions_and_deleted_message_markers(): void
    {
        Storage::fake('local');

        $customer = $this->userWithRole(Role::CUSTOMER);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $messageId = $this->withToken($token)
            ->post("/api/tickets/{$ticket->id}/reply", [
                'attachments' => [UploadedFile::fake()->create('clip.mp4', 256, 'video/mp4')],
            ])
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.attachments.0.is_video', true)
            ->json('data.ticket.messages.0.id');

        $this->withToken($token)
            ->postJson("/api/tickets/{$ticket->id}/messages/{$messageId}/reactions", ['reaction' => '🔥'])
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.reactions.0.reaction', '🔥')
            ->assertJsonPath('data.ticket.messages.0.reactions.0.count', 1);

        $this->assertDatabaseHas('ticket_message_reactions', [
            'ticket_message_id' => $messageId,
            'user_id' => $customer->id,
            'reaction' => '🔥',
        ]);

        $this->withToken($token)
            ->deleteJson("/api/tickets/{$ticket->id}/messages/{$messageId}")
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.is_deleted', true)
            ->assertJsonPath('data.ticket.messages.0.message', null)
            ->assertJsonPath('data.ticket.messages.0.attachments', []);

        $this->assertDatabaseHas('ticket_messages', [
            'id' => $messageId,
            'deleted_by' => $customer->id,
        ]);
        $this->assertDatabaseMissing('ticket_attachments', [
            'ticket_message_id' => $messageId,
        ]);

    }

    public function test_administrator_can_delete_customer_message(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $message = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'Customer message for admin deletion',
            'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
        ]);

        $this->withToken($admin->createToken('admin-delete')->plainTextToken)
            ->deleteJson("/api/tickets/{$ticket->id}/messages/{$message->id}")
            ->assertOk();

        $this->assertDatabaseHas('ticket_messages', [
            'id' => $message->id,
            'deleted_by' => $admin->id,
        ]);
    }

    public function test_message_reaction_is_one_selected_emoji_per_user(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $message = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'React to this',
            'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
        ]);
        $token = $customer->createToken('reaction-test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/tickets/{$ticket->id}/messages/{$message->id}/reactions", ['reaction' => "\u{1F525}"])
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.reactions.0.reaction', "\u{1F525}")
            ->assertJsonPath('data.ticket.messages.0.reactions.0.user_ids.0', $customer->id);

        $this->withToken($token)
            ->postJson("/api/tickets/{$ticket->id}/messages/{$message->id}/reactions", ['reaction' => "\u{1F44D}"])
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.reactions.0.reaction', "\u{1F44D}");

        $this->assertSame(1, TicketMessageReaction::query()
            ->where('ticket_message_id', $message->id)
            ->where('user_id', $customer->id)
            ->count());
        $this->assertDatabaseMissing('ticket_message_reactions', [
            'ticket_message_id' => $message->id,
            'user_id' => $customer->id,
            'reaction' => "\u{1F525}",
        ]);

        $this->withToken($token)
            ->postJson("/api/tickets/{$ticket->id}/messages/{$message->id}/reactions", ['reaction' => "\u{1F44D}"])
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.reactions', []);

        $this->assertDatabaseMissing('ticket_message_reactions', [
            'ticket_message_id' => $message->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_message_reaction_update_cleans_existing_duplicate_user_reactions(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $message = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'React to this duplicate state',
            'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
        ]);

        TicketMessageReaction::query()->create([
            'ticket_message_id' => $message->id,
            'user_id' => $customer->id,
            'reaction' => "\u{1F525}",
        ]);
        TicketMessageReaction::query()->create([
            'ticket_message_id' => $message->id,
            'user_id' => $customer->id,
            'reaction' => "\u{1F44D}",
        ]);

        $this->withToken($customer->createToken('reaction-cleanup-test')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/messages/{$message->id}/reactions", ['reaction' => "\u2764\uFE0F"])
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.reactions.0.reaction', "\u2764\uFE0F")
            ->assertJsonPath('data.ticket.messages.0.reactions.0.count', 1);

        $this->assertSame(1, TicketMessageReaction::query()
            ->where('ticket_message_id', $message->id)
            ->where('user_id', $customer->id)
            ->count());
        $this->assertDatabaseHas('ticket_message_reactions', [
            'ticket_message_id' => $message->id,
            'user_id' => $customer->id,
            'reaction' => "\u2764\uFE0F",
        ]);
    }

    public function test_ticket_reply_can_reference_customer_message(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $customerMessage = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'The upload button is missing.',
            'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
        ]);

        $this->withToken($admin->createToken('reply-reference-test')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/reply", [
                'message' => 'I can reproduce this.',
                'reply_to_message_id' => $customerMessage->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.1.reply_to.id', $customerMessage->id)
            ->assertJsonPath('data.ticket.messages.1.reply_to.message', 'The upload button is missing.')
            ->assertJsonPath('data.ticket.messages.1.reply_to.user.id', $customer->id);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'message' => 'I can reproduce this.',
            'reply_to_message_id' => $customerMessage->id,
        ]);
    }

    public function test_customer_cannot_reply_to_internal_note_reference(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $internalNote = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Private handling notes.',
            'message_type' => TicketMessage::TYPE_INTERNAL_NOTE,
        ]);

        $this->withToken($customer->createToken('hidden-reply-reference-test')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/reply", [
                'message' => 'Reply to hidden note',
                'reply_to_message_id' => $internalNote->id,
            ])
            ->assertForbidden();
    }

    public function test_ticket_conversation_broadcast_includes_public_message_but_hides_internal_notes(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $publicMessage = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'Public customer context.',
            'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
        ]);
        $internalNote = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'message' => 'Private internal context.',
            'message_type' => TicketMessage::TYPE_INTERNAL_NOTE,
        ]);

        $publicPayload = (new TicketConversationUpdated($ticket, $admin, 'message.created', $publicMessage->id))->broadcastWith();
        $internalPayload = (new TicketConversationUpdated($ticket, $admin, 'message.internal_note_created', $internalNote->id))->broadcastWith();

        $this->assertSame('Public customer context.', $publicPayload['message']['message']);
        $this->assertFalse($publicPayload['requires_refetch']);
        $this->assertNull($internalPayload['message']);
        $this->assertTrue($internalPayload['requires_refetch']);
    }

    public function test_viewing_ticket_marks_visible_messages_as_read(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $agent = $this->userWithRole(Role::AGENT);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $message = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'message' => 'Please review this update.',
            'message_type' => TicketMessage::TYPE_AGENT_REPLY,
        ]);

        $this->withToken($customer->createToken('read-test')->plainTextToken)
            ->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.read_by.0.user.id', $customer->id);

        $this->assertDatabaseHas('ticket_message_reads', [
            'ticket_message_id' => $message->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_conversation_moderation_blocks_obfuscated_configured_terms_before_persistence(): void
    {
        BlockedWord::query()->create([
            'word' => 'frobble',
            'normalized_word' => 'frobble',
            'severity' => 'high',
            'action' => 'block',
            'is_active' => true,
        ]);
        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this->withToken($customer->createToken('moderation-mask')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/reply", ['message' => 'This is fr0bble bad.'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Your message contains language that is not permitted. Please revise it and try again.')
            ->assertJsonPath('errors.message.0', 'Prohibited language detected.');

        $this->assertDatabaseMissing('ticket_messages', ['ticket_id' => $ticket->id]);

        $this->assertDatabaseHas('conversation_moderation_events', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'action' => 'block',
            'severity' => 'high',
            'review_status' => 'pending',
        ]);
    }

    public function test_conversation_moderation_blocks_configured_terms(): void
    {
        BlockedWord::query()->create([
            'word' => 'crudzor',
            'normalized_word' => 'crudzor',
            'severity' => 'critical',
            'action' => 'block',
            'is_active' => true,
        ]);
        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this->withToken($customer->createToken('moderation-block')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/reply", ['message' => 'c r u d z o r'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Your message contains language that is not permitted. Please revise it and try again.');

        $this->assertDatabaseMissing('ticket_messages', ['ticket_id' => $ticket->id]);
        $this->assertDatabaseHas('conversation_moderation_events', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'action' => 'block',
        ]);
    }

    public function test_default_english_and_tagalog_profanity_terms_are_blocked(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);

        foreach (['fuck', 'putang ina'] as $index => $message) {
            $ticket = Ticket::factory()->create([
                'customer_id' => $customer->id,
                'priority_id' => $this->priority->id,
                'status' => Ticket::STATUS_OPEN,
            ]);

            $this->withToken($customer->createToken("default-moderation-{$index}")->plainTextToken)
                ->postJson("/api/tickets/{$ticket->id}/reply", ['message' => $message])
                ->assertForbidden()
                ->assertJsonPath('message', 'Your message contains language that is not permitted. Please revise it and try again.');

            $this->assertDatabaseMissing('ticket_messages', ['ticket_id' => $ticket->id]);
            $this->assertDatabaseHas('conversation_moderation_events', [
                'ticket_id' => $ticket->id,
                'user_id' => $customer->id,
                'action' => 'block',
            ]);
        }
    }

    public function test_advanced_foul_word_obfuscation_examples_are_blocked(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $examples = [
            'fucking',
            'FUCKING',
            'Fucking',
            "f\u{00FC}cking",
            "F\u{00DC}CKING",
            "f\u{00FC}ck!ng",
            'fcking',
            'f u c k i n g',
            'f.u.c.k.i.n.g',
            'f-u-c-k-i-n-g',
            'f_u_c_k_i_n_g',
            'fuuucking',
            'fucccking',
            "f\u{200B}u\u{200B}c\u{200B}k\u{200B}i\u{200B}n\u{200B}g",
            'f@ck',
            'sh1t',
            'b!tch',
            'p u t a n g i n a',
        ];

        foreach ($examples as $index => $message) {
            $ticket = Ticket::factory()->create([
                'customer_id' => $customer->id,
                'priority_id' => $this->priority->id,
                'status' => Ticket::STATUS_OPEN,
            ]);

            $this->withToken($customer->createToken("advanced-moderation-{$index}")->plainTextToken)
                ->postJson("/api/tickets/{$ticket->id}/reply", ['message' => $message])
                ->assertForbidden()
                ->assertJsonPath('message', 'Your message contains language that is not permitted. Please revise it and try again.')
                ->assertJsonPath('errors.message.0', 'Prohibited language detected.');

            $this->assertDatabaseMissing('ticket_messages', ['ticket_id' => $ticket->id]);
        }
    }

    public function test_internal_notes_use_the_same_advanced_moderation_enforcement(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $ticket = Ticket::factory()->create([
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this->withToken($admin->createToken('internal-moderation')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/internal-note", ['message' => "F\u{00DC}CK!NG"])
            ->assertForbidden()
            ->assertJsonPath('errors.message.0', 'Prohibited language detected.');

        $this->assertDatabaseMissing('ticket_messages', ['ticket_id' => $ticket->id]);
    }

    public function test_advanced_moderation_false_positive_and_unicode_safe_text_is_allowed(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $allowedMessages = [
            'class assignment passed by the assistant during assessment',
            'classic Scunthorpe support request',
            'Magandang araw, kumusta ang ticket ko?',
            "Zo\u{00EB} Nu\u{00F1}ez sent a caf\u{00E9} receipt \u{1F44D}",
        ];

        foreach ($allowedMessages as $index => $message) {
            $ticket = Ticket::factory()->create([
                'customer_id' => $customer->id,
                'priority_id' => $this->priority->id,
                'status' => Ticket::STATUS_OPEN,
            ]);

            $this->withToken($customer->createToken("allowed-moderation-{$index}")->plainTextToken)
                ->postJson("/api/tickets/{$ticket->id}/reply", ['message' => $message])
                ->assertOk()
                ->assertJsonPath('data.ticket.messages.0.message', $message);
        }
    }

    public function test_advanced_moderation_handles_long_separator_input_without_persisting_blocked_content(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $message = str_repeat('safe text ', 600).' f-u-c-k-i-n-g';

        $this->withToken($customer->createToken('long-moderation')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/reply", ['message' => $message])
            ->assertForbidden()
            ->assertJsonPath('errors.message.0', 'Prohibited language detected.');

        $this->assertDatabaseMissing('ticket_messages', ['ticket_id' => $ticket->id]);
    }

    public function test_existing_blocked_conversation_messages_are_hidden_by_cleanup_migration(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);
        $message = TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'message' => 'fuck',
            'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
        ]);

        $migration = include database_path('migrations/2026_08_17_031000_remove_existing_blocked_conversation_messages.php');
        $migration->up();

        $message->refresh();
        $this->assertNotNull($message->deleted_at);
        $this->assertSame('Message removed by moderation.', $message->message);

        $this->withToken($customer->createToken('moderation-cleanup')->plainTextToken)
            ->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.message', null)
            ->assertJsonPath('data.ticket.messages.0.is_deleted', true)
            ->assertJsonMissing(['fuck']);
    }

    public function test_conversation_moderation_uses_word_boundaries(): void
    {
        BlockedWord::query()->create([
            'word' => 'ass',
            'normalized_word' => 'ass',
            'severity' => 'medium',
            'action' => 'mask_flag',
            'is_active' => true,
        ]);
        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this->withToken($customer->createToken('moderation-boundary')->plainTextToken)
            ->postJson("/api/tickets/{$ticket->id}/reply", ['message' => 'The class assignment passed.'])
            ->assertOk()
            ->assertJsonPath('data.ticket.messages.0.message', 'The class assignment passed.');

        $this->assertDatabaseMissing('conversation_moderation_events', ['ticket_id' => $ticket->id]);
    }

    public function test_search_and_status_filter_are_paginated(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        Ticket::factory()->create([
            'subject' => 'Printer outage',
            'status' => Ticket::STATUS_OPEN,
            'priority_id' => $this->priority->id,
        ]);
        Ticket::factory()->create([
            'subject' => 'Unrelated',
            'status' => Ticket::STATUS_CANCELLED,
            'priority_id' => $this->priority->id,
        ]);

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->getJson('/api/tickets?search=Printer&status=open&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.pagination.per_page', 1)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.tickets.0.subject', 'Printer outage');
    }

    public function test_administrator_ticket_mutations_are_audited(): void
    {
        Storage::fake('local');

        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $customer = $this->userWithRole(Role::CUSTOMER);
        $agent = $this->userWithRole(Role::AGENT);
        $agent->forceFill(['primary_department_id' => $this->department->id])->save();
        $agent->departments()->sync([$this->department->id]);
        $token = $admin->createToken('test')->plainTextToken;

        $ticketId = $this->withToken($token)->postJson('/api/tickets', [
            'customer_id' => $customer->id,
            'subject' => 'Admin created ticket',
            'description' => 'Created by an administrator for the customer.',
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
        ])->assertCreated()->json('data.ticket.id');

        $this->withToken($token)
            ->postJson("/api/tickets/{$ticketId}/assign", ['assigned_agent_id' => $agent->id])
            ->assertOk();

        $this->withToken($token)
            ->patchJson("/api/tickets/{$ticketId}/status", ['status' => Ticket::STATUS_IN_PROGRESS])
            ->assertOk();

        $this->withToken($token)
            ->patchJson("/api/tickets/{$ticketId}/status", ['status' => Ticket::STATUS_RESOLVED])
            ->assertOk();

        $this->withToken($token)
            ->postJson("/api/tickets/{$ticketId}/reply", ['message' => 'Admin-visible reply audit'])
            ->assertOk();

        $this->withToken($token)
            ->post("/api/tickets/{$ticketId}/attachments", [
                'attachments' => [UploadedFile::fake()->image('audit.png')],
            ])
            ->assertOk();

        foreach ([
            AuditAction::TICKET_CREATED,
            AuditAction::TICKET_ASSIGNED,
            AuditAction::TICKET_STATUS_CHANGED,
            AuditAction::TICKET_RESOLVED,
            AuditAction::TICKET_REPLIED,
            AuditAction::TICKET_ATTACHMENT_UPLOADED,
        ] as $action) {
            $this->assertDatabaseHas('audit_logs', [
                'user_id' => $admin->id,
                'action' => $action,
                'entity_type' => 'ticket',
                'entity_id' => $ticketId,
            ]);
        }

        $this->assertNotNull(
            \App\Models\AuditLog::query()
                ->where('action', AuditAction::TICKET_CREATED)
                ->where('entity_id', $ticketId)
                ->value('created_at')
        );
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
