<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'primary_department_id',
        'name',
        'email',
        'phone',
        'company',
        'customer_label',
        'telegram_profile',
        'password',
        'is_active',
        'web_notifications_enabled',
        'email_notifications_enabled',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'web_notifications_enabled' => 'boolean',
            'email_notifications_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function primaryDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'primary_department_id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_users')->withPivot('created_at');
    }

    public function hasRole(string ...$roles): bool
    {
        $slug = $this->role?->slug;

        return $slug !== null && in_array($slug, $roles, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function safeProfile(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'customer_label' => $this->customer_label,
            'telegram_profile' => $this->telegram_profile,
            'is_active' => $this->is_active,
            'web_notifications_enabled' => $this->web_notifications_enabled,
            'email_notifications_enabled' => $this->email_notifications_enabled,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'slug' => $this->role->slug,
            ] : null,
        ];
    }
}
