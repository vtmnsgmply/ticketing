<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Role;
use App\Models\SlaRule;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
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

    public function test_customer_dashboard_is_customer_scoped(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $other = $this->userWithRole(Role::CUSTOMER);
        Ticket::factory()->create(['customer_id' => $customer->id, 'status' => Ticket::STATUS_OPEN, 'priority_id' => $this->priority->id]);
        Ticket::factory()->create(['customer_id' => $customer->id, 'status' => Ticket::STATUS_IN_PROGRESS, 'priority_id' => $this->priority->id]);
        Ticket::factory()->create(['customer_id' => $other->id, 'status' => Ticket::STATUS_OPEN, 'priority_id' => $this->priority->id]);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->getJson('/api/customer/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.open', 1)
            ->assertJsonPath('data.summary.in_progress', 1)
            ->assertJsonCount(2, 'data.recent_tickets');

        $agent = $this->userWithRole(Role::AGENT);
        Sanctum::actingAs($agent);
        $this->getJson('/api/customer/dashboard')
            ->assertForbidden();
    }

    public function test_customer_ticket_scope_and_internal_notes_are_enforced(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $other = $this->userWithRole(Role::CUSTOMER);
        $agent = $this->userWithRole(Role::AGENT);
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id, 'priority_id' => $this->priority->id]);
        $otherTicket = Ticket::factory()->create(['customer_id' => $other->id, 'priority_id' => $this->priority->id]);
        TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'message' => 'Private note',
            'message_type' => TicketMessage::TYPE_INTERNAL_NOTE,
        ]);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->getJson("/api/tickets/{$otherTicket->id}")
            ->assertStatus(404);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonMissing(['message' => 'Private note']);
    }

    public function test_customer_create_reply_reopen_and_notifications(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $agent = $this->userWithRole(Role::AGENT);
        $manager = $this->userWithRole(Role::MANAGER);
        $manager->forceFill(['primary_department_id' => $this->department->id])->save();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson('/api/tickets', [
                'customer_id' => 999999,
                'subject' => 'Need help',
                'description' => 'Customer request',
                'department_id' => $this->department->id,
                'category_id' => $this->category->id,
                'priority_id' => $this->priority->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.ticket.customer.id', $customer->id);

        $ticketId = $response->json('data.ticket.id');
        $ticket = Ticket::query()->findOrFail($ticketId);
        $ticket->forceFill(['assigned_agent_id' => $agent->id, 'status' => Ticket::STATUS_OPEN])->save();

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/tickets/{$ticketId}/reply", ['message' => 'More details'])
            ->assertOk();

        $ticket->forceFill(['status' => Ticket::STATUS_CLOSED])->save();
        $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/tickets/{$ticketId}/reply", ['message' => 'Closed reply'])
            ->assertForbidden();

        $ticket->forceFill(['status' => Ticket::STATUS_RESOLVED, 'resolved_at' => now()])->save();
        $this->withToken($customer->createToken('test')->plainTextToken)
            ->postJson("/api/tickets/{$ticketId}/reopen")
            ->assertOk()
            ->assertJsonPath('data.ticket.status', Ticket::STATUS_OPEN);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $agent->id,
            'ticket_id' => $ticketId,
            'type' => 'ticket_reopened',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $manager->id,
            'ticket_id' => $ticketId,
            'type' => 'ticket_reopened',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'ticket_id' => $ticketId,
            'type' => 'ticket_reopened',
        ]);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->getJson('/api/customer/notifications')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_customer_reopen_limit_is_enforced(): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'customer_reopen_limit'],
            ['value' => '1', 'value_type' => 'integer', 'is_public' => true, 'created_at' => now(), 'updated_at' => now()],
        );

        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'department_id' => $this->department->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status' => Ticket::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/tickets/{$ticket->id}/reopen")
            ->assertOk()
            ->assertJsonPath('data.ticket.status', Ticket::STATUS_OPEN);

        $ticket->fresh()->forceFill(['status' => Ticket::STATUS_RESOLVED, 'resolved_at' => now()])->save();

        $this->withToken($token)
            ->postJson("/api/tickets/{$ticket->id}/reopen")
            ->assertForbidden()
            ->assertJsonPath('message', 'Customers can reopen a resolved ticket up to 1 times.');
    }

    public function test_customer_profile_and_password_change_are_restricted(): void
    {
        $customer = $this->userWithRole(Role::CUSTOMER);
        $adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->putJson('/api/customer/profile', [
                'name' => 'Updated Customer',
                'email' => 'updated@example.com',
                'phone' => '555-0100',
                'company' => 'Acme',
                'role_id' => $adminRole->id,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Updated Customer');

        $fresh = $customer->fresh('role');
        $this->assertTrue($fresh->hasRole(Role::CUSTOMER));
        $this->assertTrue($fresh->is_active);

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->putJson('/api/customer/password', [
                'current_password' => 'wrong',
                'password' => 'newpass',
                'password_confirmation' => 'newpass',
            ])
            ->assertForbidden();

        $this->withToken($customer->createToken('test')->plainTextToken)
            ->putJson('/api/customer/password', [
                'current_password' => 'password',
                'password' => 'newpass',
                'password_confirmation' => 'newpass',
            ])
            ->assertOk();

        $this->postJson('/api/login', ['email' => 'updated@example.com', 'password' => 'newpass'])->assertOk();
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password'),
        ]);
    }
}
