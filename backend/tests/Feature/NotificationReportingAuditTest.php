<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditLogService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationReportingAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Mail::fake();
    }

    public function test_shared_notification_endpoints_are_owner_scoped(): void
    {
        $user = $this->user(Role::CUSTOMER);
        $other = $this->user(Role::CUSTOMER);
        $own = Notification::query()->create(['user_id' => $user->id, 'type' => 'ticket_created', 'channel' => 'web', 'title' => 'Own', 'message' => 'Own']);
        Notification::query()->create(['user_id' => $other->id, 'type' => 'ticket_created', 'channel' => 'web', 'title' => 'Other', 'message' => 'Other']);

        Sanctum::actingAs($user);
        $this->getJson('/api/notifications/unread-count')->assertOk()->assertJsonPath('data.unread_count', 1);
        $this->patchJson("/api/notifications/{$own->id}/read")->assertOk();
        $this->patchJson('/api/notifications/999999/read')->assertNotFound();
        $this->assertTrue($own->fresh()->is_read);
        $this->assertFalse(Notification::query()->where('user_id', $other->id)->firstOrFail()->is_read);
    }

    public function test_reports_are_role_aware(): void
    {
        $admin = $this->user(Role::ADMINISTRATOR);
        $manager = $this->user(Role::MANAGER);
        $customer = $this->user(Role::CUSTOMER);
        $priority = Priority::factory()->create();
        Ticket::factory()->create(['priority_id' => $priority->id, 'status' => Ticket::STATUS_OPEN]);

        Sanctum::actingAs($customer);
        $this->getJson('/api/admin/reports/overview')->assertForbidden();
        Sanctum::actingAs($manager);
        $this->getJson('/api/manager/reports/overview')->assertOk();
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/reports/overview')->assertOk()->assertJsonPath('data.summary.created', 1);
    }

    public function test_audit_logs_are_admin_only_and_sanitize_sensitive_values(): void
    {
        $admin = $this->user(Role::ADMINISTRATOR);
        $manager = $this->user(Role::MANAGER);
        app(AuditLogService::class)->record($admin, 'settings.email_updated', 'system_settings', null, null, ['smtp_password' => 'secret', 'nested' => ['api_key' => 'abc']]);

        Sanctum::actingAs($manager);
        $this->getJson('/api/admin/audit-logs')->assertForbidden();
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/audit-logs')->assertOk()->assertJsonPath('data.audit_logs.0.new_values.smtp_password', '[REDACTED]');
        $log = AuditLog::query()->firstOrFail();
        $this->assertSame('[REDACTED]', $log->new_values['nested']['api_key']);
    }

    private function user(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id])->fresh('role');
    }
}
