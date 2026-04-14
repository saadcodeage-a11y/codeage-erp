<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function submit(User $user, array $validated, ?int $employeeId = null): LeaveRequest
    {
        $resolvedEmployeeId = $employeeId ?? ($user->role === 'Employee' ? $user->employee_id : ($validated['employee_id'] ?? null));

        if (! $resolvedEmployeeId) {
            throw ValidationException::withMessages([
                'employee_id' => 'An employee must be selected for this leave request.',
            ]);
        }

        $employee = Employee::findOrFail($resolvedEmployeeId);
        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $daysCount = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date'])) + 1;

        if ($leaveType->max_days && $daysCount > $leaveType->max_days) {
            throw ValidationException::withMessages([
                'end_date' => "This leave type allows a maximum of {$leaveType->max_days} days.",
            ]);
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
            throw ValidationException::withMessages([
                'start_date' => 'This employee already has a pending or approved leave request for the selected dates.',
            ]);
        }

        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'requested_by_user_id' => $user->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): LeaveRequest
    {
        abort_if($leaveRequest->status !== 'pending', 422, 'Only pending leave requests can be cancelled.');
        abort_if($user->role === 'Employee' && (int) $leaveRequest->employee_id !== (int) $user->employee_id, 403);

        $leaveRequest->update([
            'status' => 'cancelled',
            'reviewer_notes' => $leaveRequest->reviewer_notes,
            'reviewed_by_user_id' => $user->id,
            'reviewed_at' => now(),
        ]);

        return $leaveRequest->refresh();
    }
}
