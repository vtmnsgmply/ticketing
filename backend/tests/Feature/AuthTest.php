<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_user_can_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'agent@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'agent@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                ],
            ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_invalid_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'customer@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid email or password.');
    }

    public function test_invalid_email_validation_errors_are_returned(): void
    {
        $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => '',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_disabled_user_cannot_log_in(): void
    {
        User::factory()->inactive()->create([
            'email' => 'disabled@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'disabled@example.com',
            'password' => 'secret-password',
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account is currently unavailable. Please contact the administrator.');
    }

    public function test_unauthenticated_request_cannot_access_me(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized.');
    }

    public function test_authenticated_user_can_access_me(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonMissingPath('data.user.password');
    }

    public function test_role_restrictions_are_enforced(): void
    {
        $customer = User::factory()->create();
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/admin/auth-check')
            ->assertForbidden()
            ->assertJsonPath('message', 'Forbidden.');
    }

    public function test_administrator_can_access_role_protected_route(): void
    {
        $role = Role::query()->create([
            'name' => 'Administrator',
            'slug' => Role::ADMINISTRATOR,
        ]);
        $admin = User::factory()->create([
            'role_id' => $role->id,
        ]);
        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/admin/auth-check')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_logout_invalidates_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, PersonalAccessToken::query()->count());
    }
}
