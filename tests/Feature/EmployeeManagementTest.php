<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
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
}
