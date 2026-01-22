<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;

class DashboardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Departments
        $deptEng = Department::create(['name' => 'Engineering']); // Will have count populated dynamically on dashboard
        $deptHR = Department::create(['name' => 'Human Resources']);
        $deptSales = Department::create(['name' => 'Sales']);
        $deptDesign = Department::create(['name' => 'Design']);
        $deptMkt = Department::create(['name' => 'Marketing']);

        // 2. Employees (Total 8 active, as per screenshot roughly)
        // Creating mix of employees
        $departments = [$deptEng, $deptHR, $deptSales, $deptDesign, $deptMkt];
        
        $names = [
            ['Rajesh Kumar', 'Senior Developer', 'active', 'EMP001'],
            ['Priya Sharma', 'Product Manager', 'active', 'EMP002'],
            ['Amit Patel', 'UX Designer', 'invited', null],
            ['Sneha Reddy', 'HR Manager', 'active', 'EMP004'],
            ['Vikram Singh', 'DevOps Engineer', 'inactive', 'EMP005'],
            ['Ananya Desai', 'Marketing Lead', 'active', 'EMP006'],
            ['Karthik Iyer', 'Backend Developer', 'invited', null],
            ['Neha Gupta', 'QA Engineer', 'active', 'EMP008'],
        ];

        foreach ($names as $idx => $data) {
            Employee::create([
                'full_name' => $data[0], // full_name
                'email' => strtolower(str_replace(' ', '.', $data[0])) . '@codeage.com',
                'designation' => $data[1],
                'status' => $data[2],
                'employee_id' => $data[3],
                'department_id' => $departments[$idx % count($departments)]->id,
                'hiring_date' => Carbon::now()->subMonths(rand(1,6)),
            ]);
        }
        
        // 3. Activity Logs
        ActivityLog::create([
            'description' => 'Rajesh Kumar joined as Software Engineer',
            'type' => 'success',
            'created_at' => Carbon::now()->subMinutes(5)
        ]);

        ActivityLog::create([
            'description' => 'Priya Sharma joined as HR Manager',
            'type' => 'success',
            'created_at' => Carbon::now()->subMinutes(15)
        ]);

        ActivityLog::create([
            'description' => 'Invitation sent to Amit Patel',
            'type' => 'info',
            'created_at' => Carbon::now()->subMinutes(30)
        ]);

        $this->command->info('Dashboard data seeded successfully.');
    }
}
