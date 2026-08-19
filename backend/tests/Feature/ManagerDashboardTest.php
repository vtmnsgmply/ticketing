<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Role;
use App\Models\SlaRule;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManagerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;
    private Department $otherDepartment;
    private Category $category;
    private Priority $priority;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->department = Department::factory()->create();
        $this->otherDepartment = Department::factory()->create();
        $this->category = Category::factory()->create(['department_id' => $this->department->id]);
        $this->priority = Priority::factory()->create(['slug' => 'high', 'sort_order' => 30]);
        SlaRule::query()->create(['priority_id' => $this->priority->id, 'first_response_minutes' => 60, 'resolution_minutes' => 480, 'is_active' => true]);
    }

    public function test_manager_dashboard_and_ticket_access_are_department_scoped(): void
    {
        $manager = $this->user(Role::MANAGER, $this->department);
        $agent = $this->user(Role::AGENT, $this->department);
        $customer = $this->user(Role::CUSTOMER);
        $allowed = Ticket::factory()->create(['department_id' => $this->department->id, 'assigned_agent_id' => $agent->id, 'priority_id' => $this->priority->id]);
        $blocked = Ticket::factory()->create(['department_id' => $this->otherDepartment->id, 'priority_id' => $this->priority->id]);

        $this->getJson('/api/manager/dashboard')->assertUnauthorized();
        Sanctum::actingAs($customer);
        $this->getJson('/api/manager/dashboard')->assertForbidden();

        Sanctum::actingAs($manager);
        $this->getJson('/api/manager/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.open', 1)
            ->assertJsonCount(1, 'data.team_workload');

        $this->getJson("/api/tickets/{$allowed->id}")->assertOk();
        $this->getJson("/api/tickets/{$blocked->id}")->assertStatus(404);
    }

    public function test_manager_assignment_validates_agent_department(): void
    {
        $manager = $this->user(Role::MANAGER, $this->department);
        $agent = $this->user(Role::AGENT, $this->department);
        $otherAgent = $this->user(Role::AGENT, $this->otherDepartment);
        $ticket = Ticket::factory()->create(['department_id' => $this->department->id, 'category_id' => $this->category->id, 'priority_id' => $this->priority->id]);

        Sanctum::actingAs($manager);
        $this->postJson("/api/tickets/{$ticket->id}/assign", ['assigned_agent_id' => $otherAgent->id])->assertForbidden();
        $this->postJson("/api/tickets/{$ticket->id}/assign", ['assigned_agent_id' => $agent->id])
            ->assertOk()
            ->assertJsonPath('data.ticket.assigned_agent.id', $agent->id);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'action' => 'ticket_assigned']);
        $this->assertDatabaseHas('notifications', ['user_id' => $agent->id, 'type' => 'ticket_assigned', 'channel' => 'web']);
    }

    private function user(string $roleSlug, ?Department $department = null): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_department_id' => $department?->id]);
        if ($department !== null) {
            $user->departments()->sync([$department->id]);
        }

        return $user->fresh('role');
    }
}
