<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_department_announcement(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
        ]);

        $department = Department::create([
            'name' => 'HR',
            'total_employees' => 0,
        ]);

        $response = $this->actingAs($user)->postJson(route('announcements.store'), [
            'title' => 'HR Meeting',
            'message' => 'Monthly HR sync at 3 PM.',
            'is_global' => false,
            'is_active' => true,
            'department_ids' => [$department->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('announcements', [
            'title' => 'HR Meeting',
            'is_global' => false,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('announcement_department', [
            'department_id' => $department->id,
        ]);
    }

    public function test_employee_only_sees_global_and_own_department_announcements(): void
    {
        $hrDepartment = Department::create(['name' => 'HR', 'total_employees' => 1]);
        $accountsDepartment = Department::create(['name' => 'Accounts', 'total_employees' => 1]);

        $employee = Employee::create([
            'full_name' => 'HR Employee',
            'email' => 'hr@example.com',
            'employee_id' => 'CA-E-101',
            'department_id' => $hrDepartment->id,
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => 'Employee',
            'employee_id' => $employee->id,
        ]);

        $creator = User::factory()->create([
            'role' => 'Super Admin',
        ]);

        Announcement::create([
            'title' => 'Global Update',
            'message' => 'For everyone.',
            'is_global' => true,
            'is_active' => true,
            'created_by' => $creator->id,
            'published_at' => now(),
        ]);

        $hrAnnouncement = Announcement::create([
            'title' => 'HR Only',
            'message' => 'For HR team.',
            'is_global' => false,
            'is_active' => true,
            'created_by' => $creator->id,
            'published_at' => now(),
        ]);
        $hrAnnouncement->departments()->sync([$hrDepartment->id]);

        $accountsAnnouncement = Announcement::create([
            'title' => 'Accounts Only',
            'message' => 'For Accounts team.',
            'is_global' => false,
            'is_active' => true,
            'created_by' => $creator->id,
            'published_at' => now(),
        ]);
        $accountsAnnouncement->departments()->sync([$accountsDepartment->id]);

        Announcement::create([
            'title' => 'Old Notice',
            'message' => 'No longer active.',
            'is_global' => true,
            'is_active' => false,
            'created_by' => $creator->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('announcements.index'));

        $response->assertOk();
        $response->assertSee('Global Update');
        $response->assertSee('HR Only');
        $response->assertDontSee('Accounts Only');
        $response->assertDontSee('Old Notice');
    }

    public function test_accounts_manager_can_access_announcements_module(): void
    {
        $user = User::factory()->create([
            'role' => 'Accounts Manager',
        ]);

        $response = $this->actingAs($user)->get(route('announcements.index'));

        $response->assertOk();
        $response->assertSee('Announcements');
    }
}
