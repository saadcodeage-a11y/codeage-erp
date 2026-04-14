<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PerformanceEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createEmployee(array $attributes = []): Employee
    {
        $department = Department::firstOrCreate(['name' => 'Engineering']);

        return Employee::create(array_merge([
            'full_name' => 'Performance Employee',
            'email' => 'performance-' . uniqid() . '@example.com',
            'status' => 'active',
            'department_id' => $department->id,
            'designation' => 'Engineer',
            'employee_id' => 'CA-E-' . random_int(100, 999),
        ], $attributes));
    }

    public function test_team_manager_can_create_monthly_evaluation_for_assigned_employee(): void
    {
        $manager = User::factory()->create(['role' => 'Team Manager']);
        $employee = $this->createEmployee([
            'team_manager_user_id' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->post(route('performance.store'), [
            'employee_id' => $employee->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'monthly_period' => '2026-04',
        ]);

        $evaluation = PerformanceEvaluation::firstOrFail();

        $response->assertRedirect(route('performance.show', $evaluation));
        $this->assertDatabaseHas('performance_evaluations', [
            'employee_id' => $employee->id,
            'manager_user_id' => $manager->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => '2026-04-01 00:00:00',
            'period_end' => '2026-04-30 00:00:00',
            'status' => PerformanceEvaluation::STATUS_MANAGER_DRAFT,
        ]);
    }

    public function test_team_manager_can_submit_manager_contribution(): void
    {
        $manager = User::factory()->create(['role' => 'Team Manager']);
        $employee = $this->createEmployee([
            'team_manager_user_id' => $manager->id,
        ]);
        $evaluation = PerformanceEvaluation::create([
            'employee_id' => $employee->id,
            'manager_user_id' => $manager->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'status' => PerformanceEvaluation::STATUS_MANAGER_DRAFT,
        ]);

        $response = $this->actingAs($manager)->post(route('performance.manager.update', $evaluation), [
            'manager_performance' => 4,
            'manager_punctuality' => 5,
            'manager_behaviour' => 4,
            'manager_learning' => 4,
            'manager_participation' => 5,
            'manager_feedback' => 'Strong month with consistent team collaboration.',
        ]);

        $response->assertRedirect(route('performance.show', $evaluation));
        $this->assertDatabaseHas('performance_evaluations', [
            'id' => $evaluation->id,
            'status' => PerformanceEvaluation::STATUS_PENDING_HR,
            'manager_performance' => 4,
            'manager_feedback' => 'Strong month with consistent team collaboration.',
        ]);
    }

    public function test_hr_manager_can_finalize_evaluation(): void
    {
        $hrUser = User::factory()->create(['role' => 'HR Manager']);
        $manager = User::factory()->create(['role' => 'Team Manager']);
        $employee = $this->createEmployee([
            'team_manager_user_id' => $manager->id,
        ]);
        $evaluation = PerformanceEvaluation::create([
            'employee_id' => $employee->id,
            'manager_user_id' => $manager->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_BIANNUAL,
            'period_start' => '2026-01-01',
            'period_end' => '2026-06-30',
            'status' => PerformanceEvaluation::STATUS_PENDING_HR,
            'manager_performance' => 4,
            'manager_punctuality' => 4,
            'manager_behaviour' => 5,
            'manager_learning' => 4,
            'manager_participation' => 4,
            'manager_feedback' => 'Manager contribution submitted.',
            'manager_submitted_at' => now(),
        ]);

        $response = $this->actingAs($hrUser)->post(route('performance.finalize', $evaluation), [
            'hr_performance' => 4,
            'hr_punctuality' => 4,
            'hr_behaviour' => 5,
            'hr_learning' => 4,
            'hr_participation' => 4,
            'hr_feedback' => 'Finalized after HR calibration.',
        ]);

        $response->assertRedirect(route('performance.show', $evaluation));
        $this->assertDatabaseHas('performance_evaluations', [
            'id' => $evaluation->id,
            'status' => PerformanceEvaluation::STATUS_FINALIZED,
            'hr_finalized_by_user_id' => $hrUser->id,
            'hr_feedback' => 'Finalized after HR calibration.',
        ]);
    }

    public function test_team_manager_cannot_finalize_or_access_unassigned_employee_evaluation(): void
    {
        $manager = User::factory()->create(['role' => 'Team Manager']);
        $otherManager = User::factory()->create(['role' => 'Team Manager']);
        $employee = $this->createEmployee([
            'team_manager_user_id' => $otherManager->id,
        ]);
        $evaluation = PerformanceEvaluation::create([
            'employee_id' => $employee->id,
            'manager_user_id' => $otherManager->id,
            'evaluation_type' => PerformanceEvaluation::TYPE_MONTHLY,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'status' => PerformanceEvaluation::STATUS_PENDING_HR,
        ]);

        $this->actingAs($manager)->get(route('performance.show', $evaluation))->assertForbidden();
        $this->actingAs($manager)->post(route('performance.finalize', $evaluation), [
            'hr_performance' => 4,
            'hr_punctuality' => 4,
            'hr_behaviour' => 4,
            'hr_learning' => 4,
            'hr_participation' => 4,
            'hr_feedback' => 'Not allowed.',
        ])->assertForbidden();
    }
}
