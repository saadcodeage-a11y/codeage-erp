<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('roles')->updateOrInsert(
            ['name' => 'Team Manager'],
            ['created_at' => $now, 'updated_at' => $now]
        );

        $roles = Role::query()->pluck('id', 'name');
        $modules = Role::availableModules();

        $teamManagerPermissions = [
            'dashboard' => ['read' => true, 'create' => false, 'edit' => false],
            'team_management' => ['read' => true, 'create' => false, 'edit' => true],
            'announcements' => ['read' => true, 'create' => false, 'edit' => false],
        ];

        foreach ($roles as $roleName => $roleId) {
            foreach ($modules as $module => $label) {
                $permissions = $roleName === 'Team Manager'
                    ? ($teamManagerPermissions[$module] ?? ['read' => false, 'create' => false, 'edit' => false])
                    : ($module === 'team_management'
                        ? ['read' => false, 'create' => false, 'edit' => false]
                        : null);

                if ($permissions === null) {
                    continue;
                }

                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'module' => $module],
                    [
                        'can_read' => $permissions['read'],
                        'can_create' => $permissions['create'],
                        'can_edit' => $permissions['edit'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $teamManagerRoleId = DB::table('roles')->where('name', 'Team Manager')->value('id');

        DB::table('role_permissions')->where('module', 'team_management')->delete();

        if ($teamManagerRoleId) {
            DB::table('users')->where('role', 'Team Manager')->update(['role' => 'Employee']);
            DB::table('roles')->where('id', $teamManagerRoleId)->delete();
        }
    }
};
