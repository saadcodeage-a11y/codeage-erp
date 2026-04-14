<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveRequestService;
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

    public function store(Request $request, LeaveRequestService $leaveRequestService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:2000',
        ]);

        $leaveRequestService->submit($user, $validated);

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

    public function cancel(Request $request, LeaveRequest $leaveRequest, LeaveRequestService $leaveRequestService)
    {
        $leaveRequestService->cancel($request->user(), $leaveRequest);

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
