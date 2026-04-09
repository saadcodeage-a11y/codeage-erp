<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')->get(['id', 'name']);
        $timestamp = now();

        foreach ($roles as $role) {
            $permissions = match ($role->name) {
                'Super Admin' => ['read' => true, 'create' => true, 'edit' => true],
                'HR Manager' => ['read' => true, 'create' => true, 'edit' => true],
                default => ['read' => false, 'create' => false, 'edit' => false],
            };

            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $role->id, 'module' => 'attendance_management'],
                [
                    'can_read' => $permissions['read'],
                    'can_create' => $permissions['create'],
                    'can_edit' => $permissions['edit'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('module', 'attendance_management')->delete();
    }
};
