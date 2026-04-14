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
        $this->authorizeEmployeePortal($request->user());

        $employee = $this->linkedEmployee($request->user());
        $activeTab = $request->query('tab', 'profile');

        if (! $employee) {
            return response()->view('self-service.index', [
                'employee' => null,
                'activeTab' => $activeTab,
            ], 403);
        }

        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $employee->load(['department', 'teamManager', 'bank']);

        $payrollRecords = EmployeePayrollRecord::query()
            ->with('payrollRun')
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun')
            ->get()
            ->sortByDesc(fn (EmployeePayrollRecord $record) => $record->payrollRun?->pay_period_month?->timestamp ?? 0)
            ->values();

        $securitySnapshots = $employee->securityFundSnapshots()
            ->latest('snapshot_month')
            ->limit(12)
            ->get();

        $attendanceRecords = $employee->attendanceRecords()
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('attendance_date')
            ->get();

        $leaveRequests = $employee->leaveRequests()
            ->with(['leaveType', 'reviewedBy'])
            ->latest()
            ->limit(20)
            ->get();

        $performanceEvaluations = $employee->performanceEvaluations()
            ->where('status', PerformanceEvaluation::STATUS_FINALIZED)
            ->with(['manager', 'hrFinalizer'])
            ->orderByDesc('period_start')
            ->limit(24)
            ->get();

        $attendanceSummary = [
            'present' => $attendanceRecords->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
            'late' => $attendanceRecords->where('status', AttendanceRecord::STATUS_LATE)->count(),
            'absent' => $attendanceRecords->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
            'incomplete' => $attendanceRecords->where('status', AttendanceRecord::STATUS_INCOMPLETE)->count(),
            'holiday' => $attendanceRecords->where('status', AttendanceRecord::STATUS_HOLIDAY)->count(),
            'weekend' => $attendanceRecords->where('status', AttendanceRecord::STATUS_WEEKEND)->count(),
        ];

        $leaveSummary = [
            'pending' => $leaveRequests->where('status', 'pending')->count(),
            'approved' => $leaveRequests->where('status', 'approved')->count(),
            'rejected' => $leaveRequests->where('status', 'rejected')->count(),
            'cancelled' => $leaveRequests->where('status', 'cancelled')->count(),
        ];

        $portalStats = [
            'latestNetSalary' => optional($payrollRecords->first())->net_salary,
            'securityBalance' => optional($securitySnapshots->first())->balance_in_account,
            'currentMonthAttendanceRows' => $attendanceRecords->count(),
            'finalizedReviews' => $performanceEvaluations->count(),
        ];

        return view('self-service.index', [
            'employee' => $employee,
            'activeTab' => $activeTab,
            'payrollRecords' => $payrollRecords,
            'securitySnapshots' => $securitySnapshots,
            'attendanceRecords' => $attendanceRecords,
            'attendanceSummary' => $attendanceSummary,
            'leaveRequests' => $leaveRequests,
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'leaveSummary' => $leaveSummary,
            'performanceEvaluations' => $performanceEvaluations,
            'portalStats' => $portalStats,
            'currentMonthLabel' => $monthStart->format('F Y'),
        ]);
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
            ->route('self-service.index', ['tab' => 'leave'])
            ->with('success', 'Leave request submitted successfully.');
    }

    public function cancelLeave(Request $request, LeaveRequest $leaveRequest, LeaveRequestService $leaveRequestService)
    {
        $this->authorizeEmployeePortal($request->user());
        $employee = $this->linkedEmployeeOrFail($request->user());
        abort_if((int) $leaveRequest->employee_id !== (int) $employee->id, 403);

        $leaveRequestService->cancel($request->user(), $leaveRequest);

        return redirect()
            ->route('self-service.index', ['tab' => 'leave'])
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
        abort_if($user->role !== 'Employee', 403, 'Self Service is only available from an employee account.');
    }
}
