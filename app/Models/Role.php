<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const MODULES = [
        'dashboard' => 'Dashboard',
        'self_service' => 'Self Service',
        'employees' => 'Employees',
        'team_management' => 'My Team',
        'performance_management' => 'Performance',
        'leave_management' => 'Leave Management',
        'attendance_management' => 'Attendance Management',
        'payroll_management' => 'Payroll',
        'reports' => 'Reports',
        'announcements' => 'Announcements',
        'user_management' => 'User Management',
        'settings' => 'Settings',
        'templates' => 'Templates',
        'activity_logs' => 'Activity Logs',
    ];

    protected $fillable = [
        'name',
    ];

    public function permissions()
    {
        return $this->hasMany(RolePermission::class)->orderBy('module');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role', 'name');
    }

    public static function availableModules(): array
    {
        return self::MODULES;
    }

    public function permissionsByModule(): array
    {
        $permissions = $this->permissions->keyBy('module');

        return collect(self::availableModules())->mapWithKeys(function (string $label, string $module) use ($permissions) {
            $permission = $permissions->get($module);

            return [
                $module => [
                    'label' => $label,
                    'read' => (bool) ($permission?->can_read),
                    'create' => (bool) ($permission?->can_create),
                    'edit' => (bool) ($permission?->can_edit),
                ],
            ];
        })->all();
    }
}
