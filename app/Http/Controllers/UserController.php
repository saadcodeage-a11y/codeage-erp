<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $counts = [
            'total' => User::count(),
            'super_admins' => User::where('role', 'Super Admin')->count(),
            'hr_managers' => User::where('role', 'HR Manager')->count(),
            'accounts_managers' => User::where('role', 'Accounts Manager')->count(),
        ];

        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        $users = $query->with('employee')->latest()->paginate(10)->withQueryString();
        
        // Get employees who are active and not already assigned to a user
        $assignedEmployeeIds = User::whereNotNull('employee_id')->pluck('employee_id');
        $employees = Employee::where('status', 'active')
            ->whereNotIn('id', $assignedEmployeeIds)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email', 'employee_id']);

        return view('users.index', compact('users', 'counts', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string',
            'password' => 'required|string|min:8',
            'two_factor_enabled' => 'nullable|boolean',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['two_factor_enabled'] = $request->boolean('two_factor_enabled');
        
        // Generate User ID (USR001, USR002, etc)
        $lastUser = User::where('user_id', 'like', 'USR%')->orderBy('user_id', 'desc')->first();
        if ($lastUser) {
            $lastId = intval(substr($lastUser->user_id, 3));
            $newId = 'USR' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newId = 'USR001';
        }
        $validated['user_id'] = $newId;

        User::create($validated);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User created successfully.']);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|string',
            'two_factor_enabled' => 'nullable|boolean',
            'employee_id' => 'nullable|exists:employees,id',
        ]);

        $validated['two_factor_enabled'] = $request->boolean('two_factor_enabled');

        $user->update($validated);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User updated successfully.']);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Password reset successfully.']);
        }

        return back()->with('success', 'Password reset successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
        }

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
