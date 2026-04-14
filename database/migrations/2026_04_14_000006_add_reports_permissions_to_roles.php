<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = Role::query()->pluck('id', 'name');
        $now = now();

        $defaults = [
            'HR Manager' => ['read' => true, 'create' => false, 'edit' => false],
            'Accounts Manager' => ['read' => true, 'create' => false, 'edit' => false],
            'Team Manager' => ['read' => false, 'create' => false, 'edit' => false],
            'Employee' => ['read' => false, 'create' => false, 'edit' => false],
        ];

        foreach ($roles as $roleName => $roleId) {
            $permissions = $defaults[$roleName] ?? ['read' => false, 'create' => false, 'edit' => false];

            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'module' => 'reports'],
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

    public function down(): void
    {
        DB::table('role_permissions')->where('module', 'reports')->delete();
    }
};
