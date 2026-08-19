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
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private Department $otherDepartment;
    private Category $category;
    private Priority $priority;
    private Priority $highPriority;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->department = Department::factory()->create();
        $this->otherDepartment = Department::factory()->create();
        $this->category = Category::factory()->create(['department_id' => $this->department->id]);
        $this->priority = Priority::factory()->create(['slug' => 'medium', 'sort_order' => 20]);
        $this->highPriority = Priority::factory()->create(['slug' => 'high', 'sort_order' => 30]);
        foreach ([$this->priority, $this->highPriority] as $priority) {
            SlaRule::query()->create([
                'priority_id' => $priority->id,
                'first_response_minutes' => 240,
                'resolution_minutes' => 1440,
                'is_active' => true,
            ]);
        }
    }

    public function test_staff_dashboard_is_agent_only_and_scoped(): void
    {
        $agent = $this->agent();
        $customer = $this->userWithRole(Role::CUSTOMER);
        Ticket::factory()->create(['department_id' => $this->department->id, 'assigned_agent_id' => $agent->id, 'status' => Ticket::STATUS_OPEN, 'priority_id' => $this->priority->id]);
        Ticket::factory()->create(['department_id' => $this->department->id, 'assigned_agent_id' => null, 'status' => Ticket::STATUS_NEW, 'priority_id' => $this->highPriority->id]);
        Ticket::factory()->create(['department_id' => $this->otherDepartment->id, 'assigned_agent_id' => null, 'status' => Ticket::STATUS_NEW, 'priority_id' => $this->priority->id]);

        $this->getJson('/api/staff/dashboard')->assertUnauthorized();

        Sanctum::actingAs($customer);
        $this->getJson('/api/staff/dashboard')->assertForbidden();

        Sanctum::actingAs($agent);
        $this->getJson('/api/staff/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.assigned_to_me', 1)
            ->assertJsonPath('data.summary.unassigned', 1)
            ->assertJsonPath('data.summary.high_priority', 1)
            ->assertJsonCount(1, 'data.unassigned');
    }

    public function test_agent_ticket_access_and_self_assignment_are_scoped(): void
    {
        $agent = $this->agent();
        $ticket = Ticket::factory()->create(['department_id' => $this->department->id, 'assigned_agent_id' => null, 'status' => Ticket::STATUS_OPEN, 'priority_id' => $this->priority->id]);
        $otherTicket = Ticket::factory()->create(['department_id' => $this->otherDepartment->id, 'assigned_agent_id' => null, 'status' => Ticket::STATUS_OPEN, 'priority_id' => $this->priority->id]);

        $token = $agent->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson("/api/tickets/{$ticket->id}")->assertOk();
        $this->withToken($token)->getJson("/api/tickets/{$otherTicket->id}")->assertStatus(404);

        $this->withToken($token)
            ->postJson("/api/tickets/{$ticket->id}/assign", ['assigned_agent_id' => $agent->id])
            ->assertOk()
            ->assertJsonPath('data.ticket.assigned_agent.id', $agent->id);

        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => 'ticket_assigned']);
        $this->assertDatabaseHas('notifications', ['user_id' => $agent->id, 'type' => 'ticket_assigned']);
    }

    public function test_agent_reply_internal_note_status_and_priority_behaviour(): void
    {
        $agent = $this->agent();
        $customer = $this->userWithRole(Role::CUSTOMER);
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'department_id' => $this->department->id,
            'assigned_agent_id' => $agent->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
            'priority_id' => $this->priority->id,
        ]);

        $token = $agent->createToken('test')->plainTextToken;
        $this->withToken($token)->postJson("/api/tickets/{$ticket->id}/internal-note", ['message' => 'Private context'])->assertOk();
        $this->assertNull($ticket->fresh()->first_responded_at);

        $this->withToken($token)->postJson("/api/tickets/{$ticket->id}/reply", ['message' => 'Customer-visible reply'])->assertOk();
        $firstResponse = $ticket->fresh()->first_responded_at;
        $this->assertNotNull($firstResponse);

        $this->withToken($token)->postJson("/api/tickets/{$ticket->id}/reply", ['message' => 'Second reply'])->assertOk();
        $this->assertTrue($firstResponse->equalTo($ticket->fresh()->first_responded_at));

        $this->withToken($token)->patchJson("/api/tickets/{$ticket->id}/priority", ['priority_id' => $this->highPriority->id])
            ->assertOk()
            ->assertJsonPath('data.ticket.priority.id', $this->highPriority->id);

        $this->withToken($token)->patchJson("/api/tickets/{$ticket->id}/status", ['status' => Ticket::STATUS_RESOLVED])
            ->assertOk()
            ->assertJsonPath('data.ticket.status', Ticket::STATUS_RESOLVED);

        Sanctum::actingAs($customer);
        $this->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonMissing(['message' => 'Private context'])
            ->assertJsonFragment(['message' => 'Customer-visible reply']);
    }

    public function test_staff_profile_restrictions_and_notifications(): void
    {
        $agent = $this->agent();
        $adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
        $ticket = Ticket::factory()->create(['department_id' => $this->department->id, 'assigned_agent_id' => $agent->id, 'priority_id' => $this->priority->id]);
        TicketMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => User::factory()->create()->id,
            'message' => 'Customer response',
            'message_type' => TicketMessage::TYPE_CUSTOMER_REPLY,
        ]);
        app(\App\Services\NotificationService::class)->ticketEvent('customer_replied', $ticket);

        $token = $agent->createToken('test')->plainTextToken;
        $this->withToken($token)
            ->putJson('/api/staff/profile', [
                'name' => 'Agent Updated',
                'email' => 'agent-updated@example.com',
                'phone' => '555-0199',
                'role_id' => $adminRole->id,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Agent Updated');

        $fresh = $agent->fresh('role');
        $this->assertTrue($fresh->hasRole(Role::AGENT));
        $this->assertTrue($fresh->is_active);

        $this->withToken($token)->putJson('/api/staff/password', [
            'current_password' => 'password',
            'password' => 'newpass',
            'password_confirmation' => 'newpass',
        ])->assertOk();

        $this->withToken($token)->getJson('/api/staff/notifications')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);
    }

    private function agent(): User
    {
        $agent = $this->userWithRole(Role::AGENT);
        $agent->forceFill(['primary_department_id' => $this->department->id])->save();
        $agent->departments()->sync([$this->department->id]);

        return $agent->fresh('role');
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
