<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeePayrollRecord;
use App\Models\LeaveType;
use App\Models\PerformanceEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $activeTab = request()->query('tab', 'account');
        $employee = null;
        $payrollRecords = collect();
        $securitySnapshots = collect();
        $attendanceRecords = collect();
        $attendanceSummary = [
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'incomplete' => 0,
            'holiday' => 0,
            'weekend' => 0,
        ];
        $leaveRequests = collect();
        $leaveTypes = collect();
        $leaveSummary = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'cancelled' => 0,
        ];
        $performanceEvaluations = collect();
        $portalStats = [
            'latestNetSalary' => null,
            'securityBalance' => null,
            'currentMonthAttendanceRows' => 0,
            'finalizedReviews' => 0,
        ];
        $currentMonthLabel = now()->startOfMonth()->format('F Y');

        if ($user->role !== 'Super Admin' && $user->employee_id) {
            $employee = Employee::query()
                ->with(['department', 'teamManager', 'bank'])
                ->find($user->employee_id);

            if ($employee) {
                $monthStart = now()->startOfMonth();
                $monthEnd = now()->endOfMonth();

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

                $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

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

                $currentMonthLabel = $monthStart->format('F Y');
            }
        }

        return view('profile.index', compact(
            'user',
            'activeTab',
            'employee',
            'payrollRecords',
            'securitySnapshots',
            'attendanceRecords',
            'attendanceSummary',
            'leaveRequests',
            'leaveTypes',
            'leaveSummary',
            'performanceEvaluations',
            'portalStats',
            'currentMonthLabel'
        ));
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Password updated successfully.']);
        }

        return back()->with('status', 'password-updated');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'], // 2MB Max
        ]);

        $user = Auth::user();
        
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && file_exists(public_path('storage/' . $user->avatar))) {
                @unlink(public_path('storage/' . $user->avatar));
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $path]);
            
            return response()->json([
                'success' => true, 
                'message' => 'Profile picture updated successfully.',
                'avatar_url' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded.'], 400);
    }

    public function toggleTwoFactor(Request $request)
    {
        $user = Auth::user();
        $user->two_factor_enabled = !$user->two_factor_enabled;
        $user->save();

        $status = $user->two_factor_enabled ? 'enabled' : 'disabled';

        return response()->json([
            'success' => true,
            'message' => "Two-Factor Authentication has been {$status}.",
            'enabled' => $user->two_factor_enabled
        ]);
    }
}
