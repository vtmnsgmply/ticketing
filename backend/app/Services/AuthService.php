<?php

namespace App\Services;

use App\Exceptions\AccountDisabledException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repository\UserRepository;
use App\Support\AuditAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * @param array{email: string, password: string} $credentials
     * @return array{user: array<string, mixed>, token: string}
     */
    public function login(array $credentials, ?Request $request = null): array
    {
        $user = $this->users->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->audit->record(null, AuditAction::AUTH_LOGIN_FAILED, 'auth', null, null, null, $request, ['email' => $credentials['email']]);
            throw new InvalidCredentialsException('Invalid email or password.');
        }

        if (! $user->is_active) {
            $this->audit->record($user, AuditAction::AUTH_LOGIN_FAILED, 'auth', $user->id, null, null, $request, ['reason' => 'account_disabled']);
            throw new AccountDisabledException('Your account is currently unavailable. Please contact the administrator.');
        }

        $this->users->touchLastLogin($user);
        $user = $this->users->findById((int) $user->id);
        $this->audit->record($user, AuditAction::AUTH_LOGIN_SUCCESS, 'auth', $user->id, null, null, $request);

        return [
            'user' => $this->profile($user),
            'token' => $user->createToken('frontend')->plainTextToken,
        ];
    }

    public function logout(User $user, ?Request $request = null): void
    {
        $this->audit->record($user, AuditAction::AUTH_LOGOUT, 'auth', $user->id, null, null, $request);
        $token = $user->currentAccessToken();

        if ($token !== null) {
            $token->delete();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(?User $user): array
    {
        if ($user === null) {
            throw new InvalidCredentialsException('Unauthorized.');
        }

        $user->loadMissing('role');

        return $user->safeProfile();
    }
}
