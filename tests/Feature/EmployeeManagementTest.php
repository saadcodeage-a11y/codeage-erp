<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\EmployeeEmploymentHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_employee_list()
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'IT']);
        Employee::create([
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'department_id' => $dept->id,
            'employee_id' => 'EMP100'
        ]);

        $response = $this->actingAs($user)->get('/employees');

        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee('EMP100');
    }

    public function test_can_filter_employees_by_status()
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'IT']);
        
        Employee::create(['full_name' => 'Active User', 'email' => 'active@ex.com', 'status' => 'active', 'department_id' => $dept->id, 'employee_id' => 'E1']);
        Employee::create(['full_name' => 'Invited User', 'email' => 'invited@ex.com', 'status' => 'invited', 'department_id' => $dept->id, 'employee_id' => 'E2']);

        // Check Active Tab (Default)
        $response = $this->actingAs($user)->get('/employees');
        $response->assertSee('Active User');
        $response->assertDontSee('Invited User');

        // Check Invited Tab
        $response = $this->actingAs($user)->get('/employees?status=invited');
        $response->assertSee('Invited User');
        $response->assertDontSee('Active User');
    }

    public function test_can_create_employee_with_files()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'HR']);

        $response = $this->actingAs($user)->post('/employees', [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'department_id' => $dept->id,
            'designation' => 'Manager',
            'hiring_date' => now()->format('Y-m-d'),
            'cnic_front' => \Illuminate\Http\UploadedFile::fake()->create('cnic.pdf', 100),
        ]);

        $employee = Employee::where('email', 'jane@example.com')->first();
        $response->assertRedirect(route('employees.show', $employee));
        
        $this->assertDatabaseHas('employees', ['email' => 'jane@example.com', 'full_name' => 'Jane Doe']);
        // Verify file storage
        $this->assertNotNull($employee->cnic_front_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($employee->cnic_front_path);
    }

    public function test_can_view_single_employee_details()
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'IT']);
        $employee = Employee::create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'active',
            'department_id' => $dept->id,
            'designation' => 'Tester',
            'employee_id' => 'EMP999'
        ]);

        $response = $this->actingAs($user)->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertSee('Test User');
        $response->assertSee('Tester'); // Matched case from creation
        $response->assertSee('EMP999');
    }

    public function test_can_edit_employee()
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'IT']);
        $employee = Employee::create([
            'full_name' => 'Old Name',
            'email' => 'old@example.com',
            'status' => 'active',
            'department_id' => $dept->id,
            'designation' => 'Dev',
            'employee_id' => 'EMP111'
        ]);

        $response = $this->actingAs($user)->put(route('employees.update', $employee), [
            'full_name' => 'New Name',
            'email' => 'old@example.com', // Keep same
            'department_id' => $dept->id,
            'designation' => 'Lead Dev',
        ]);

        $response->assertRedirect(route('employees.show', $employee));
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'full_name' => 'New Name', 'designation' => 'Lead Dev']);
    }

    public function test_employee_can_only_be_marked_inactive_with_a_reason()
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'IT']);
        $employee = Employee::create([
            'full_name' => 'Status User',
            'email' => 'status@example.com',
            'status' => 'active',
            'department_id' => $dept->id,
            'designation' => 'Developer',
            'employee_id' => 'EMP115',
        ]);

        $response = $this->actingAs($user)->patchJson("/employees/{$employee->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['inactive_reason']);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'status' => 'active',
            'inactive_reason' => null,
        ]);
    }

    public function test_can_mark_employee_inactive_with_reason()
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'IT']);
        $employee = Employee::create([
            'full_name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'status' => 'active',
            'department_id' => $dept->id,
            'designation' => 'Developer',
            'employee_id' => 'EMP116',
        ]);

        $response = $this->actingAs($user)->patchJson("/employees/{$employee->id}/status", [
            'status' => 'inactive',
            'inactive_reason' => 'Position is on hold.',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'status' => 'inactive',
            'inactive_reason' => 'Position is on hold.',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'subject_id' => $employee->id,
            'subject_type' => Employee::class,
            'description' => 'Employee Inactive User status changed to inactive. Reason: Position is on hold.',
        ]);

        $histories = $employee->fresh()->employmentHistories()->orderBy('effective_from')->get();

        $this->assertCount(1, $histories);
        $this->assertNotNull($histories->first()->effective_to);
    }

    public function test_employment_history_is_versioned_when_job_details_change()
    {
        $user = User::factory()->create();
        $deptOne = Department::create(['name' => 'IT']);
        $deptTwo = Department::create(['name' => 'Operations']);

        $employee = Employee::create([
            'full_name' => 'Timeline User',
            'email' => 'timeline@example.com',
            'status' => 'active',
            'department_id' => $deptOne->id,
            'designation' => 'Developer',
            'payroll_status' => 'Paid',
            'employee_id' => 'EMP120',
            'hiring_date' => now()->subMonth()->toDateString(),
        ]);

        $this->assertCount(1, $employee->employmentHistories);

        $response = $this->actingAs($user)->put(route('employees.update', $employee), [
            'full_name' => 'Timeline User',
            'email' => 'timeline@example.com',
            'department_id' => $deptTwo->id,
            'designation' => 'Senior Developer',
            'payroll_status' => 'Internship',
        ]);

        $response->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $histories = $employee->employmentHistories()->orderBy('effective_from')->get();
        $closedHistory = $histories->firstWhere('effective_to', '!=', null);
        $activeHistory = $histories->firstWhere('effective_to', null);

        $this->assertCount(2, $histories);
        $this->assertNotNull($closedHistory);
        $this->assertNotNull($activeHistory);
        $this->assertSame('Developer', $closedHistory->designation);
        $this->assertNotNull($closedHistory->effective_to);
        $this->assertSame('Senior Developer', $activeHistory->designation);
        $this->assertSame('Internship', $activeHistory->payroll_status);
        $this->assertSame($deptTwo->id, $activeHistory->department_id);
        $this->assertNull($activeHistory->effective_to);
    }

    public function test_employee_detail_page_shows_employment_history_and_related_activity_logs()
    {
        $user = User::factory()->create();
        $dept = Department::create(['name' => 'IT']);

        $employee = Employee::create([
            'full_name' => 'Logs User',
            'email' => 'logs@example.com',
            'status' => 'active',
            'department_id' => $dept->id,
            'designation' => 'Analyst',
            'payroll_status' => 'Paid',
            'employee_id' => 'EMP130',
        ]);

        $history = $employee->employmentHistories()->firstOrFail();

        ActivityLog::create([
            'description' => 'Employee profile reviewed',
            'type' => 'info',
            'subject_id' => $employee->id,
            'subject_type' => Employee::class,
        ]);

        ActivityLog::create([
            'description' => 'Payroll changed to Paid',
            'type' => 'success',
            'subject_id' => $history->id,
            'subject_type' => EmployeeEmploymentHistory::class,
        ]);

        $response = $this->actingAs($user)->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertSee('Employment Timeline');
        $response->assertSee('Analyst');
        $response->assertSee('Paid');
        $response->assertSee('Employee profile reviewed');
        $response->assertSee('Payroll changed to Paid');
    }
}
