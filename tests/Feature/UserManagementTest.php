<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_management_page_lists_default_roles(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
        ]);

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('User Management');
        $response->assertSee('Super Admin');
        $response->assertSee('HR Manager');
        $response->assertSee('Accounts Manager');
        $response->assertSee('Employee');
        $response->assertSee('Leave Management');
        $response->assertSee('Attendance Management');
        $response->assertSee('>8<', false);
        $response->assertSee('Modules');
    }

    public function test_can_create_role_with_module_permissions(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
        ]);

        $response = $this->actingAs($user)->postJson(route('roles.store'), [
            'name' => 'Operations Lead',
            'permissions' => [
                'dashboard' => ['read' => true, 'create' => false, 'edit' => false],
                'employees' => ['read' => true, 'create' => true, 'edit' => true],
                'leave_management' => ['read' => true, 'create' => true, 'edit' => false],
                'attendance_management' => ['read' => true, 'create' => false, 'edit' => false],
                'user_management' => ['read' => true, 'create' => false, 'edit' => false],
                'settings' => ['read' => false, 'create' => false, 'edit' => false],
                'templates' => ['read' => true, 'create' => false, 'edit' => true],
                'activity_logs' => ['read' => true, 'create' => false, 'edit' => false],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('roles', ['name' => 'Operations Lead']);

        $role = Role::where('name', 'Operations Lead')->firstOrFail();

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'module' => 'employees',
            'can_read' => true,
            'can_create' => true,
            'can_edit' => true,
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'module' => 'leave_management',
            'can_read' => true,
            'can_create' => true,
            'can_edit' => false,
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'module' => 'attendance_management',
            'can_read' => true,
            'can_create' => false,
            'can_edit' => false,
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'module' => 'settings',
            'can_read' => false,
            'can_create' => false,
            'can_edit' => false,
        ]);
    }
}
