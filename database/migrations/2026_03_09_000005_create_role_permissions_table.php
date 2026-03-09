<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('module');
            $table->boolean('can_read')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->timestamps();

            $table->unique(['role_id', 'module']);
        });

        $modules = ['dashboard', 'employees', 'user_management', 'settings', 'templates', 'activity_logs'];
        $timestamp = now();
        $roles = DB::table('roles')->get(['id', 'name']);

        $rows = [];

        foreach ($roles as $role) {
            foreach ($modules as $module) {
                $permissions = match ($role->name) {
                    'Super Admin' => ['read' => true, 'create' => true, 'edit' => true],
                    'HR Manager' => match ($module) {
                        'dashboard' => ['read' => true, 'create' => false, 'edit' => false],
                        'employees', 'user_management', 'activity_logs' => ['read' => true, 'create' => true, 'edit' => true],
                        'templates' => ['read' => true, 'create' => true, 'edit' => true],
                        default => ['read' => true, 'create' => false, 'edit' => false],
                    },
                    'Accounts Manager' => match ($module) {
                        'dashboard', 'employees', 'settings', 'templates', 'activity_logs' => ['read' => true, 'create' => false, 'edit' => false],
                        default => ['read' => false, 'create' => false, 'edit' => false],
                    },
                    'Employee' => match ($module) {
                        'dashboard' => ['read' => true, 'create' => false, 'edit' => false],
                        default => ['read' => false, 'create' => false, 'edit' => false],
                    },
                    default => ['read' => false, 'create' => false, 'edit' => false],
                };

                $rows[] = [
                    'role_id' => $role->id,
                    'module' => $module,
                    'can_read' => $permissions['read'],
                    'can_create' => $permissions['create'],
                    'can_edit' => $permissions['edit'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('role_permissions')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
