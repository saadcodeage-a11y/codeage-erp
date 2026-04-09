<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\HrLetter;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseOneWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
        ], $attributes));
    }

    protected function createEmployee(array $attributes = []): Employee
    {
        $department = Department::firstOrCreate(['name' => 'Operations']);

        return Employee::create(array_merge([
            'full_name' => 'Test Employee',
            'email' => 'employee' . uniqid() . '@example.com',
            'status' => 'active',
            'department_id' => $department->id,
            'designation' => 'Coordinator',
            'employee_id' => 'EMP' . random_int(100, 999),
            'hiring_date' => now()->subMonth()->toDateString(),
        ], $attributes));
    }

    public function test_employee_can_submit_leave_and_hr_can_approve_it(): void
    {
        $employee = $this->createEmployee([
            'full_name' => 'Leave Employee',
            'email' => 'leave-employee@example.com',
        ]);
        $employeeUser = $this->createUser('Employee', ['employee_id' => $employee->id]);
        $hrUser = $this->createUser('HR Manager');
        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'max_days' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($employeeUser)->post(route('leaves.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'reason' => 'Family commitment',
        ])->assertRedirect(route('leaves.index'));

        $leaveRequest = LeaveRequest::firstOrFail();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($hrUser)->postJson(route('leaves.approve', $leaveRequest), [
            'reviewer_notes' => 'Approved for planned leave.',
        ])->assertOk();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'reviewed_by_user_id' => $hrUser->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $leaveRequest->id,
            'subject_type' => LeaveRequest::class,
            'description' => 'Leave Employee leave request was approved',
        ]);
    }

    public function test_employee_can_cancel_own_pending_leave_request(): void
    {
        $employee = $this->createEmployee([
            'full_name' => 'Cancel Leave Employee',
            'email' => 'cancel-leave@example.com',
        ]);
        $employeeUser = $this->createUser('Employee', ['employee_id' => $employee->id]);
        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'max_days' => 5,
            'is_active' => true,
        ]);

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'requested_by_user_id' => $employeeUser->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'days_count' => 2,
            'reason' => 'Medical appointment',
            'status' => 'pending',
        ]);

        $this->actingAs($employeeUser)->postJson(route('leaves.cancel', $leaveRequest))
            ->assertOk();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'cancelled',
            'reviewed_by_user_id' => $employeeUser->id,
        ]);
    }

    public function test_hr_can_generate_and_download_employee_letter(): void
    {
        $employee = $this->createEmployee([
            'full_name' => 'Letter Employee',
            'email' => 'letter-employee@example.com',
        ]);
        $hrUser = $this->createUser('HR Manager');

        $response = $this->actingAs($hrUser)->postJson(route('employees.letters.generate', $employee), [
            'type' => 'offer',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $letter = HrLetter::firstOrFail();

        $this->assertDatabaseHas('hr_letters', [
            'id' => $letter->id,
            'employee_id' => $employee->id,
            'type' => 'offer',
            'generated_by_user_id' => $hrUser->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $letter->id,
            'subject_type' => HrLetter::class,
            'description' => 'Offer letter generated for Letter Employee',
        ]);

        $downloadResponse = $this->actingAs($hrUser)->get(route('employees.letters.download', [$employee, $letter]));

        $downloadResponse->assertOk();
        $downloadResponse->assertSee('Offer Letter', false);
    }

    public function test_accounts_manager_cannot_access_leave_management_or_hr_letter_generation(): void
    {
        $employee = $this->createEmployee([
            'full_name' => 'Restricted Employee',
            'email' => 'restricted-employee@example.com',
        ]);
        $accountsUser = $this->createUser('Accounts Manager');

        $this->actingAs($accountsUser)->get(route('leaves.index'))->assertForbidden();
        $this->actingAs($accountsUser)->post(route('employees.letters.generate', $employee), [
            'type' => 'termination',
        ])->assertForbidden();
    }
}
