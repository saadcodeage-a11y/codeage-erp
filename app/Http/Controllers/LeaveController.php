<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->get('status', 'all');

        $query = LeaveRequest::with(['employee', 'leaveType', 'requestedBy', 'reviewedBy']);

        if ($user->role === 'Employee') {
            abort_unless($user->employee_id, 403, 'Employee account is not linked to an employee record.');
            $query->where('employee_id', $user->employee_id);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $leaveRequests = $query->latest()->paginate(15)->withQueryString();
        $leaveTypes = LeaveType::orderBy('name')->get();
        $employees = Employee::where('status', 'active')->orderBy('full_name')->get(['id', 'full_name', 'employee_id']);

        $baseCounts = LeaveRequest::query();
        if ($user->role === 'Employee' && $user->employee_id) {
            $baseCounts->where('employee_id', $user->employee_id);
        }

        $counts = [
            'all' => (clone $baseCounts)->count(),
            'pending' => (clone $baseCounts)->where('status', 'pending')->count(),
            'approved' => (clone $baseCounts)->where('status', 'approved')->count(),
            'rejected' => (clone $baseCounts)->where('status', 'rejected')->count(),
            'cancelled' => (clone $baseCounts)->where('status', 'cancelled')->count(),
        ];

        return view('leaves.index', compact('leaveRequests', 'leaveTypes', 'employees', 'counts', 'status'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:2000',
        ]);

        $employeeId = $user->role === 'Employee' ? $user->employee_id : ($validated['employee_id'] ?? null);
        abort_unless($employeeId, 422, 'An employee must be selected for this leave request.');

        $employee = Employee::findOrFail($employeeId);
        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $daysCount = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date'])) + 1;

        if ($leaveType->max_days && $daysCount > $leaveType->max_days) {
            return back()->withErrors(['end_date' => "This leave type allows a maximum of {$leaveType->max_days} days."])->withInput();
        }

        $overlapExists = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhere(function ($nested) use ($validated) {
                        $nested->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']);
                    });
            })
            ->exists();

        if ($overlapExists) {
            return back()->withErrors(['start_date' => 'This employee already has a pending or approved leave request for the selected dates.'])->withInput();
        }

        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'requested_by_user_id' => $user->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('leaves.index')->with('success', 'Leave request submitted successfully.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $request->validate([
            'reviewer_notes' => 'nullable|string|max:2000',
        ]);

        $leaveRequest->update([
            'status' => 'approved',
            'reviewer_notes' => $request->reviewer_notes,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Leave request approved successfully.']);
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $request->validate([
            'reviewer_notes' => 'required|string|max:2000',
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'reviewer_notes' => $request->reviewer_notes,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Leave request rejected successfully.']);
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest)
    {
        $user = $request->user();

        abort_if($leaveRequest->status !== 'pending', 422, 'Only pending leave requests can be cancelled.');
        abort_if($user->role === 'Employee' && $leaveRequest->employee_id !== $user->employee_id, 403);

        $leaveRequest->update([
            'status' => 'cancelled',
            'reviewer_notes' => $leaveRequest->reviewer_notes,
            'reviewed_by_user_id' => $user->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Leave request cancelled successfully.']);
    }

    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'description' => 'nullable|string|max:255',
            'max_days' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        LeaveType::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'max_days' => $validated['max_days'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json(['success' => true, 'message' => 'Leave type created successfully.']);
    }

    public function updateType(Request $request, LeaveType $leaveType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('leave_types', 'name')->ignore($leaveType->id)],
            'description' => 'nullable|string|max:255',
            'max_days' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $leaveType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'max_days' => $validated['max_days'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json(['success' => true, 'message' => 'Leave type updated successfully.']);
    }

    public function destroyType(LeaveType $leaveType)
    {
        abort_if($leaveType->requests()->exists(), 422, 'This leave type is already in use.');

        $leaveType->delete();

        return response()->json(['success' => true, 'message' => 'Leave type deleted successfully.']);
    }
}
