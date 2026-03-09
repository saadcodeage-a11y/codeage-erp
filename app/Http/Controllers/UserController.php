<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $counts = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'two_factor_enabled' => User::where('two_factor_enabled', true)->count(),
            'total_roles' => Role::count(),
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

        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $roleOptions = $roles->pluck('name');
        $modules = Role::availableModules();

        return view('users.index', compact('users', 'counts', 'employees', 'roles', 'roleOptions', 'modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|exists:roles,name',
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
            'role' => 'required|exists:roles,name',
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

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = DB::transaction(function () use ($validated) {
            $role = Role::create([
                'name' => $validated['name'],
            ]);

            $this->syncRolePermissions($role, $validated['permissions'] ?? []);

            return $role;
        });

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'role' => $role->load('permissions'),
        ]);
    }

    public function updateRole(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $role) {
            $originalName = $role->name;
            $role->update([
                'name' => $validated['name'],
            ]);

            if ($originalName !== $role->name) {
                User::where('role', $originalName)->update(['role' => $role->name]);
            }

            $this->syncRolePermissions($role, $validated['permissions'] ?? []);
        });

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'role' => $role->fresh()->load('permissions'),
        ]);
    }

    public function destroyRole(Role $role)
    {
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'This role is assigned to one or more users and cannot be deleted.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
        ]);
    }

    protected function syncRolePermissions(Role $role, array $permissions): void
    {
        foreach (Role::availableModules() as $module => $label) {
            $modulePermissions = $permissions[$module] ?? [];

            $role->permissions()->updateOrCreate(
                ['module' => $module],
                [
                    'can_read' => (bool) ($modulePermissions['read'] ?? false),
                    'can_create' => (bool) ($modulePermissions['create'] ?? false),
                    'can_edit' => (bool) ($modulePermissions['edit'] ?? false),
                ]
            );
        }
    }
}
