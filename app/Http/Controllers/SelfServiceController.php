<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeePayrollRecord;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PerformanceEvaluation;
use App\Services\LeaveRequestService;
use App\Services\PayslipPdfService;
use Illuminate\Http\Request;

class SelfServiceController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('profile.index', ['tab' => 'profile']);
    }

    public function storeLeave(Request $request, LeaveRequestService $leaveRequestService)
    {
        $this->authorizeEmployeePortal($request->user());
        $employee = $this->linkedEmployeeOrFail($request->user());

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:2000',
        ]);

        $leaveRequestService->submit($request->user(), $validated, $employee->id);

        return redirect()
            ->route('profile.index', ['tab' => 'leave'])
            ->with('success', 'Leave request submitted successfully.');
    }

    public function cancelLeave(Request $request, LeaveRequest $leaveRequest, LeaveRequestService $leaveRequestService)
    {
        $this->authorizeEmployeePortal($request->user());
        $employee = $this->linkedEmployeeOrFail($request->user());
        abort_if((int) $leaveRequest->employee_id !== (int) $employee->id, 403);

        $leaveRequestService->cancel($request->user(), $leaveRequest);

        return redirect()
            ->route('profile.index', ['tab' => 'leave'])
            ->with('success', 'Leave request cancelled successfully.');
    }

    public function downloadPayslip(Request $request, EmployeePayrollRecord $payrollRecord, PayslipPdfService $payslipPdfService)
    {
        $this->authorizeEmployeePortal($request->user());
        $employee = $this->linkedEmployeeOrFail($request->user());
        abort_if((int) $payrollRecord->employee_id !== (int) $employee->id, 403);

        return $payslipPdfService->download($payrollRecord);
    }

    protected function linkedEmployee($user): ?Employee
    {
        if (! $user?->employee_id) {
            return null;
        }

        return Employee::query()
            ->whereKey($user->employee_id)
            ->first();
    }

    protected function linkedEmployeeOrFail($user): Employee
    {
        $employee = $this->linkedEmployee($user);
        abort_if(! $employee, 403, 'Employee account is not linked to an employee record.');

        return $employee;
    }

    protected function authorizeEmployeePortal($user): void
    {
        abort_if($user->role === 'Super Admin', 403, 'Self-service employee actions are not available for super admin accounts.');
    }
}
