<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\TeamPerformanceReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createEmployee(array $attributes = []): Employee
    {
        $department = Department::firstOrCreate(['name' => 'Engineering']);

        return Employee::create(array_merge([
            'full_name' => 'Assigned Employee',
            'email' => 'team-' . uniqid() . '@example.com',
            'status' => 'active',
            'department_id' => $department->id,
            'designation' => 'Developer',
            'employee_id' => 'CA-E-' . random_int(100, 999),
        ], $attributes));
    }

    public function test_team_manager_sees_only_assigned_employees(): void
    {
        $manager = User::factory()->create(['role' => 'Team Manager']);
        $assignedEmployee = $this->createEmployee([
            'full_name' => 'Assigned Team Member',
            'team_manager_user_id' => $manager->id,
        ]);
        $this->createEmployee([
            'full_name' => 'Unassigned Team Member',
        ]);

        $response = $this->actingAs($manager)->get(route('team.index'));

        $response->assertOk();
        $response->assertSee('My Team');
        $response->assertSee('Assigned Team Member');
        $response->assertDontSee('Unassigned Team Member');
    }

    public function test_team_manager_can_submit_performance_review_for_assigned_employee(): void
    {
        $manager = User::factory()->create(['role' => 'Team Manager']);
        $employee = $this->createEmployee([
            'team_manager_user_id' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->post(route('team.reviews.store', $employee), [
            'review_month' => '2026-04',
            'rating' => 4,
            'feedback' => 'Strong delivery and reliable follow-through.',
        ]);

        $response->assertRedirect(route('team.show', $employee));

        $this->assertDatabaseHas('team_performance_reviews', [
            'employee_id' => $employee->id,
            'manager_user_id' => $manager->id,
            'review_month' => '2026-04-01 00:00:00',
            'rating' => 4,
        ]);
    }

    public function test_team_manager_cannot_open_unassigned_employee_review_page(): void
    {
        $manager = User::factory()->create(['role' => 'Team Manager']);
        $employee = $this->createEmployee();

        $this->actingAs($manager)
            ->get(route('team.show', $employee))
            ->assertForbidden();
    }

    public function test_team_manager_cannot_access_payroll_leave_or_settings(): void
    {
        $manager = User::factory()->create(['role' => 'Team Manager']);

        $this->actingAs($manager)->get(route('payroll.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('leaves.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('settings.index'))->assertForbidden();
    }
}
