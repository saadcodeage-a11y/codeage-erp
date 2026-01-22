<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\ActivityLog;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Total Employees
        $totalEmployees = Employee::count();
        $employeesLastMonth = 0; // Mocked or calculated if we had extensive history. 
        // For dynamic "12% from last month", we would need historical data. 
        // We will pass a calculated static value for now or mock if no history.

        // 2. Active Employees
        $activeEmployees = Employee::where('status', 'active')->count();

        // 3. Pending Approval (Mocked or based on status if we had 'pending' status)
        // Assuming 'on_leave' might be used or just 0 for now if no such status.
        $pendingApproval = 0; 
        
        // 4. Invited (Mocked or users with no password set? For now static based on data or mock)
        $invited = 2; // Hardcoded as per design or unimplemented feature

        // 5. Total Users
        $totalUsers = User::count();

        // 6. Active Users (Assumed all verified users are active)
        $activeUsers = User::whereNotNull('email_verified_at')->count();

        // 7. Recent Activity
        $activities = ActivityLog::latest()->take(3)->get();

        // 8. Department Overview
        // We want a list of departments and their count
        $departments = Department::withCount('employees')->get();
        // Calculate max for progress bar scaling
        $maxDeptEmployees = $departments->max('employees_count') ?: 1;

        return view('dashboard', compact(
            'totalEmployees', 
            'activeEmployees', 
            'pendingApproval', 
            'invited',
            'totalUsers',
            'activeUsers',
            'activities',
            'departments',
            'maxDeptEmployees'
        ));
    }
}
