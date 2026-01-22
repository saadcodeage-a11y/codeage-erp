<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Department;

class TestEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $dept = Department::first();
        
        Employee::create([
            'email' => 'test.employee@codeage.com',
            'full_name' => 'Test Employee',
            'status' => 'invited',
            'onboarding_token' => 'TEST123TOKEN456',
            'department_id' => $dept?->id ?? 1,
        ]);
    }
}
