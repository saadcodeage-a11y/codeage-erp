<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Traits\LogsActivity;
use App\Models\Role;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_id',
        'role',
        'is_active',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
        'employee_id',
        'avatar',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function managedEmployees()
    {
        return $this->hasMany(Employee::class, 'team_manager_user_id');
    }

    public function performanceReviews()
    {
        return $this->hasMany(TeamPerformanceReview::class, 'manager_user_id');
    }

    public function performanceEvaluations()
    {
        return $this->hasMany(PerformanceEvaluation::class, 'manager_user_id');
    }

    public function roleDefinition()
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'Super Admin';
    }

    public function canAccessModule(string $module, string $ability = 'read'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $role = $this->roleDefinition()->with('permissions')->first();
        $permission = $role?->permissions->firstWhere('module', $module);

        if (! $permission) {
            return false;
        }

        return match ($ability) {
            'create' => (bool) $permission->can_create,
            'edit' => (bool) $permission->can_edit,
            default => (bool) $permission->can_read,
        };
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
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
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    public function generateTwoFactorCode()
    {
        $this->timestamps = false;
        $this->two_factor_code = rand(100000, 999999);
        $this->two_factor_expires_at = now()->addMinutes(10);
        $this->save();
    }

    public function resetTwoFactorCode()
    {
        $this->timestamps = false;
        $this->two_factor_code = null;
        $this->two_factor_expires_at = null;
        $this->save();
    }
}
