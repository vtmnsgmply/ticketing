<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BlockedWord;
use App\Models\BlockedWordVariant;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Role;
use App\Models\SlaRule;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_access_is_restricted_by_role(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();

        foreach ([Role::CUSTOMER, Role::AGENT, Role::MANAGER] as $role) {
            $user = $this->userWithRole($role);
            Sanctum::actingAs($user);
            $this->getJson('/api/admin/dashboard')
                ->assertForbidden();
        }

        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['total_users', 'open_tickets', 'overdue_tickets']]);
    }

    public function test_administrator_can_manage_users_and_departments(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $customerRole = Role::query()->where('slug', Role::CUSTOMER)->firstOrFail();
        $agentRole = Role::query()->where('slug', Role::AGENT)->firstOrFail();
        $department = Department::factory()->create();

        $response = $this->withToken($admin->createToken('test')->plainTextToken)
            ->postJson('/api/admin/users', [
                'name' => 'Support Agent',
                'email' => 'support@example.com',
                'password' => 'secret',
                'password_confirmation' => 'secret',
                'role_id' => $agentRole->id,
                'primary_department_id' => $department->id,
                'department_ids' => [$department->id],
                'is_active' => true,
            ]);

        $response->assertCreated()->assertJsonPath('data.user.email', 'support@example.com');
        $userId = $response->json('data.user.id');
        $this->assertDatabaseHas('department_users', ['department_id' => $department->id, 'user_id' => $userId]);

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->postJson('/api/admin/users', [
                'name' => 'Duplicate',
                'email' => 'support@example.com',
                'password' => 'secret',
                'password_confirmation' => 'secret',
                'role_id' => $agentRole->id,
                'is_active' => true,
            ])
            ->assertStatus(422);

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->patchJson("/api/admin/users/{$userId}/status", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.user.is_active', false);

        $this->postJson('/api/login', ['email' => 'support@example.com', 'password' => 'secret'])
            ->assertForbidden();

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->patchJson("/api/admin/users/{$userId}/status", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.user.is_active', true);

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'entity_type' => 'user']);

        $customerId = $this->withToken($admin->createToken('test')->plainTextToken)
            ->postJson('/api/admin/users', [
                'name' => 'Priority Customer',
                'email' => 'priority@example.com',
                'password' => 'secret',
                'password_confirmation' => 'secret',
                'role_id' => $customerRole->id,
                'customer_label' => 'priority',
                'telegram_profile' => '123456789',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user.customer_label', 'priority')
            ->assertJsonPath('data.user.telegram_profile', '123456789')
            ->json('data.user.id');

        $this->assertDatabaseHas('users', [
            'id' => $customerId,
            'customer_label' => 'priority',
            'telegram_profile' => '123456789',
        ]);

        $this->withToken($admin->createToken('test')->plainTextToken)
            ->putJson("/api/admin/users/{$customerId}", [
                'name' => 'Priority Customer',
                'email' => 'priority@example.com',
                'role_id' => $agentRole->id,
                'customer_label' => 'vip',
                'telegram_profile' => '987654321',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.user.customer_label', null)
            ->assertJsonPath('data.user.telegram_profile', null);
    }

    public function test_final_active_administrator_cannot_be_disabled_or_demoted(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $customerRole = Role::query()->where('slug', Role::CUSTOMER)->firstOrFail();
        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->patchJson("/api/admin/users/{$admin->id}/status", ['is_active' => false])
            ->assertForbidden()
            ->assertJsonPath('message', 'At least one active Administrator account must remain.');

        $this->withToken($token)
            ->patchJson("/api/admin/users/{$admin->id}/role", ['role_id' => $customerRole->id])
            ->assertForbidden()
            ->assertJsonPath('message', 'At least one active Administrator account must remain.');
    }

    public function test_department_category_priority_sla_settings_and_audit_endpoints_work(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $token = $admin->createToken('test')->plainTextToken;

        $departmentId = $this->withToken($token)->postJson('/api/admin/departments', [
            'name' => 'Billing',
            'slug' => 'billing',
            'description' => 'Billing desk',
            'is_active' => true,
        ])->assertOk()->json('data.department.id');

        $categoryId = $this->withToken($token)->postJson('/api/admin/categories', [
            'department_id' => $departmentId,
            'name' => 'Invoices',
            'slug' => 'invoices',
            'description' => 'Invoice requests',
            'is_active' => true,
        ])->assertOk()->json('data.category.id');

        $this->withToken($token)->patchJson("/api/admin/departments/{$departmentId}/status", ['is_active' => false])->assertOk();
        $this->withToken($token)->patchJson("/api/admin/categories/{$categoryId}/status", ['is_active' => false])->assertOk();
        $this->withToken($token)->getJson('/api/ticket-options')
            ->assertOk()
            ->assertJsonMissing(['slug' => 'billing'])
            ->assertJsonMissing(['slug' => 'invoices']);

        $priority = Priority::factory()->create(['name' => 'Expedite', 'slug' => 'expedite', 'sort_order' => 90]);
        $sla = SlaRule::query()->create([
            'priority_id' => $priority->id,
            'first_response_minutes' => 30,
            'resolution_minutes' => 240,
            'is_active' => true,
        ]);
        $ticket = Ticket::factory()->create(['priority_id' => $priority->id, 'resolution_due_at' => now()->addMinutes(240)]);
        $existingDeadline = $ticket->resolution_due_at->toDateTimeString();

        $this->withToken($token)->putJson("/api/admin/sla/{$sla->id}", [
            'first_response_minutes' => 60,
            'resolution_minutes' => 480,
            'pause_on_waiting_customer' => true,
            'use_business_hours' => false,
            'is_active' => true,
        ])->assertOk();
        $this->assertSame($existingDeadline, $ticket->fresh()->resolution_due_at->toDateTimeString());

        $this->withToken($token)->putJson('/api/admin/settings/system', [
            'ticket_prefix' => 'ADM',
            'ticket_start_number' => 20000,
            'attachment_max_size_mb' => 12,
            'allow_customer_reopen_resolved' => false,
            'customer_reopen_limit' => 2,
        ])->assertOk();
        $this->assertDatabaseHas('system_settings', ['key' => 'customer_reopen_limit', 'value' => '2']);

        DB::table('ticket_number_sequences')->updateOrInsert(['id' => 1], ['current_value' => 20000]);
        $this->withToken($token)->putJson('/api/admin/settings/system', ['ticket_start_number' => 10000])
            ->assertForbidden()
            ->assertJsonPath('message', 'Ticket start number cannot move backward into an existing ticket range.');

        $this->withToken($token)->putJson('/api/admin/settings/email', [
            'smtp_password' => 'very-secret',
            'sender_email' => 'noreply@example.com',
        ])->assertOk();
        $this->withToken($token)->getJson('/api/admin/settings/email')
            ->assertOk()
            ->assertJsonMissing(['very-secret']);

        $this->assertGreaterThan(0, AuditLog::query()->count());
        $this->withToken($token)->getJson('/api/admin/audit-logs')
            ->assertOk()
            ->assertJsonStructure(['data' => ['audit_logs', 'pagination']]);
    }

    public function test_administrator_can_manage_moderation_variants(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $token = $admin->createToken('moderation-variant-test')->plainTextToken;
        $word = BlockedWord::query()->create([
            'word' => 'sampleblocked',
            'normalized_word' => 'sampleblocked',
            'severity' => 'high',
            'action' => 'block',
            'is_active' => true,
        ]);

        $variantId = $this->withToken($token)
            ->postJson("/api/admin/moderation/blocked-words/{$word->id}/variants", [
                'variant' => 'smplblckd',
                'variant_type' => 'compressed',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.variant.variant_type', 'compressed')
            ->json('data.variant.id');

        $this->assertDatabaseHas('blocked_word_variants', [
            'id' => $variantId,
            'blocked_word_id' => $word->id,
            'normalized_variant' => 'smplblckd',
        ]);

        $this->withToken($token)
            ->getJson("/api/admin/moderation/blocked-words/{$word->id}/variants")
            ->assertOk()
            ->assertJsonPath('data.variants.0.id', $variantId);

        $this->withToken($token)
            ->putJson("/api/admin/moderation/blocked-words/{$word->id}/variants/{$variantId}", [
                'variant' => 'smpl blocked',
                'variant_type' => 'manual',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.variant.normalized_variant', 'smplblocked');

        $this->withToken($token)
            ->deleteJson("/api/admin/moderation/blocked-words/{$word->id}/variants/{$variantId}")
            ->assertOk();

        $this->assertSame(0, BlockedWordVariant::query()->whereKey($variantId)->count());
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
