<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TeamPerformanceReview;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Employee::query()
            ->with(['department', 'teamManager'])
            ->whereNotNull('team_manager_user_id');

        if (! $user->isSuperAdmin()) {
            $query->where('team_manager_user_id', $user->id);
        }

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($employeeQuery) use ($search) {
                $employeeQuery->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = (clone $query)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('full_name')
            ->paginate(12)
            ->withQueryString();

        $monthStart = now()->startOfMonth()->toDateString();
        $statsBase = clone $query;

        $stats = [
            'assigned' => (clone $statsBase)->count(),
            'active' => (clone $statsBase)->where('status', 'active')->count(),
            'inactive' => (clone $statsBase)->where('status', '!=', 'active')->count(),
            'reviewed_this_month' => TeamPerformanceReview::query()
                ->when(! $user->isSuperAdmin(), fn ($reviewQuery) => $reviewQuery->where('manager_user_id', $user->id))
                ->whereDate('review_month', $monthStart)
                ->distinct('employee_id')
                ->count('employee_id'),
        ];

        return view('team.index', compact('employees', 'stats'));
    }

    public function show(Request $request, Employee $employee)
    {
        $this->ensureCanManageEmployee($request, $employee);

        $employee->load([
            'department',
            'teamManager',
            'attendanceRecords' => fn ($query) => $query->latest('attendance_date')->limit(10),
            'performanceReviews' => fn ($query) => $query->with('manager')->latest('review_month'),
        ]);

        return view('team.show', [
            'employee' => $employee,
            'currentReviewMonth' => now()->startOfMonth()->format('Y-m'),
        ]);
    }

    public function storeReview(Request $request, Employee $employee)
    {
        $this->ensureCanManageEmployee($request, $employee);

        $validated = $request->validate([
            'review_month' => 'required|date_format:Y-m',
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'required|string|max:5000',
        ]);

        TeamPerformanceReview::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'manager_user_id' => $request->user()->id,
                'review_month' => Carbon::createFromFormat('Y-m', $validated['review_month'])->startOfMonth()->toDateString(),
            ],
            [
                'rating' => $validated['rating'],
                'feedback' => $validated['feedback'],
            ]
        );

        return redirect()
            ->route('team.show', $employee)
            ->with('success', 'Performance review saved successfully.');
    }

    protected function ensureCanManageEmployee(Request $request, Employee $employee): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        abort_if($employee->team_manager_user_id !== $user->id, 403, 'You are not authorized to manage this employee.');
    }
}
