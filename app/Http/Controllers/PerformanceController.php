<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PerformanceEvaluation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerformanceController extends Controller
{
    protected const METRICS = [
        'performance',
        'punctuality',
        'behaviour',
        'learning',
        'participation',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $query = PerformanceEvaluation::query()
            ->with(['employee.department', 'manager', 'hrFinalizer']);

        if (! $this->canManageAllEvaluations($user)) {
            $query->whereHas('employee', function ($employeeQuery) use ($user) {
                $employeeQuery->where('team_manager_user_id', $user->id);
            });
        }

        if ($search = trim((string) $request->get('search'))) {
            $query->whereHas('employee', function ($employeeQuery) use ($search) {
                $employeeQuery->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('evaluation_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $evaluations = (clone $query)
            ->orderByDesc('period_start')
            ->paginate(10)
            ->withQueryString();

        $statsBase = clone $query;
        $stats = [
            'total' => (clone $statsBase)->count(),
            'monthly' => (clone $statsBase)->where('evaluation_type', PerformanceEvaluation::TYPE_MONTHLY)->count(),
            'biannual' => (clone $statsBase)->where('evaluation_type', PerformanceEvaluation::TYPE_BIANNUAL)->count(),
            'pending_hr' => (clone $statsBase)->where('status', PerformanceEvaluation::STATUS_PENDING_HR)->count(),
            'finalized' => (clone $statsBase)->where('status', PerformanceEvaluation::STATUS_FINALIZED)->count(),
        ];

        $employees = Employee::query()
            ->where('status', 'active')
            ->when(! $this->canManageAllEvaluations($user), fn ($employeeQuery) => $employeeQuery->where('team_manager_user_id', $user->id))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_id', 'designation', 'team_manager_user_id']);

        return view('performance.index', [
            'evaluations' => $evaluations,
            'stats' => $stats,
            'employees' => $employees,
            'canFinalizeEvaluations' => $this->canFinalizeEvaluations($user),
            'metricLabels' => $this->metricLabels(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'evaluation_type' => ['required', Rule::in(array_keys(PerformanceEvaluation::types()))],
            'monthly_period' => 'nullable|date_format:Y-m',
            'biannual_year' => 'nullable|integer|min:2020|max:2100',
            'biannual_half' => 'nullable|in:1,2',
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->ensureCanAccessEmployee($user, $employee);

        [$periodStart, $periodEnd] = $this->resolvePeriod($validated);

        $evaluation = PerformanceEvaluation::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'evaluation_type' => $validated['evaluation_type'],
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
            [
                'manager_user_id' => $employee->team_manager_user_id ?: $user->id,
                'status' => PerformanceEvaluation::STATUS_MANAGER_DRAFT,
            ]
        );

        if (! $evaluation->manager_user_id) {
            $evaluation->update(['manager_user_id' => $user->id]);
        }

        return redirect()
            ->route('performance.show', $evaluation)
            ->with('success', 'Performance evaluation opened successfully.');
    }

    public function show(Request $request, PerformanceEvaluation $performance)
    {
        $performance->load(['employee.department', 'employee.teamManager', 'manager', 'hrFinalizer']);
        $this->ensureCanViewEvaluation($request->user(), $performance);

        $history = PerformanceEvaluation::query()
            ->with(['manager', 'hrFinalizer'])
            ->where('employee_id', $performance->employee_id)
            ->where('id', '!=', $performance->id)
            ->orderByDesc('period_start')
            ->limit(12)
            ->get();

        return view('performance.show', [
            'evaluation' => $performance,
            'history' => $history,
            'metricLabels' => $this->metricLabels(),
            'canFinalizeEvaluations' => $this->canFinalizeEvaluations($request->user()),
            'canEditManagerContribution' => $this->canEditManagerContribution($request->user(), $performance),
        ]);
    }

    public function updateManagerContribution(Request $request, PerformanceEvaluation $performance)
    {
        $performance->load('employee');
        $this->ensureCanEditManagerContribution($request->user(), $performance);

        $validated = $request->validate($this->metricValidationRules('manager_') + [
            'manager_feedback' => 'required|string|max:5000',
        ]);

        $payload = ['manager_feedback' => $validated['manager_feedback']];
        foreach (self::METRICS as $metric) {
            $payload['manager_' . $metric] = $validated['manager_' . $metric];
        }
        $payload['manager_submitted_at'] = now();
        $payload['status'] = PerformanceEvaluation::STATUS_PENDING_HR;
        $payload['manager_user_id'] = $performance->manager_user_id ?: $request->user()->id;

        $performance->update($payload);

        return redirect()
            ->route('performance.show', $performance)
            ->with('success', 'Manager contribution saved successfully.');
    }

    public function finalize(Request $request, PerformanceEvaluation $performance)
    {
        $performance->load('employee');
        abort_unless($this->canFinalizeEvaluations($request->user()), 403, 'You are not authorized to finalize performance evaluations.');
        abort_if($performance->status === PerformanceEvaluation::STATUS_FINALIZED, 422, 'This evaluation has already been finalized.');

        $validated = $request->validate($this->metricValidationRules('hr_') + [
            'hr_feedback' => 'required|string|max:5000',
        ]);

        $payload = ['hr_feedback' => $validated['hr_feedback']];
        foreach (self::METRICS as $metric) {
            $payload['hr_' . $metric] = $validated['hr_' . $metric];
        }
        $payload['status'] = PerformanceEvaluation::STATUS_FINALIZED;
        $payload['hr_finalized_at'] = now();
        $payload['hr_finalized_by_user_id'] = $request->user()->id;

        $performance->update($payload);

        return redirect()
            ->route('performance.show', $performance)
            ->with('success', 'Performance evaluation finalized successfully.');
    }

    protected function resolvePeriod(array $validated): array
    {
        if ($validated['evaluation_type'] === PerformanceEvaluation::TYPE_MONTHLY) {
            $start = now()->startOfMonth();
            if (! empty($validated['monthly_period'])) {
                $start = \Illuminate\Support\Carbon::createFromFormat('Y-m', $validated['monthly_period'])->startOfMonth();
            }

            return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
        }

        $year = (int) ($validated['biannual_year'] ?? now()->year);
        $half = (int) ($validated['biannual_half'] ?? 1);
        $startMonth = $half === 2 ? 7 : 1;

        $start = now()->setYear($year)->setMonth($startMonth)->startOfMonth();
        $end = $start->copy()->addMonths(5)->endOfMonth();

        return [$start->toDateString(), $end->toDateString()];
    }

    protected function ensureCanAccessEmployee($user, Employee $employee): void
    {
        if ($this->canManageAllEvaluations($user)) {
            return;
        }

        abort_if($employee->team_manager_user_id !== $user->id, 403, 'You are not authorized to manage this employee.');
    }

    protected function ensureCanViewEvaluation($user, PerformanceEvaluation $performance): void
    {
        if ($this->canManageAllEvaluations($user)) {
            return;
        }

        abort_if($performance->employee?->team_manager_user_id !== $user->id, 403, 'You are not authorized to view this evaluation.');
    }

    protected function ensureCanEditManagerContribution($user, PerformanceEvaluation $performance): void
    {
        $this->ensureCanViewEvaluation($user, $performance);

        if (! $this->canEditManagerContribution($user, $performance)) {
            abort(403, 'You are not authorized to update manager contribution for this evaluation.');
        }

        abort_if($performance->status === PerformanceEvaluation::STATUS_FINALIZED, 422, 'Finalized evaluations cannot be edited.');
    }

    protected function canManageAllEvaluations($user): bool
    {
        return $user->isSuperAdmin() || $user->role === 'HR Manager';
    }

    protected function canFinalizeEvaluations($user): bool
    {
        return $this->canManageAllEvaluations($user);
    }

    protected function canEditManagerContribution($user, PerformanceEvaluation $performance): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $performance->employee?->team_manager_user_id === (int) $user->id
            || (int) $performance->manager_user_id === (int) $user->id;
    }

    protected function metricValidationRules(string $prefix): array
    {
        $rules = [];

        foreach (self::METRICS as $metric) {
            $rules[$prefix . $metric] = 'required|integer|min:1|max:5';
        }

        return $rules;
    }

    protected function metricLabels(): array
    {
        return [
            'performance' => 'Performance',
            'punctuality' => 'Punctuality',
            'behaviour' => 'Behaviour',
            'learning' => 'Learning',
            'participation' => 'Participation',
        ];
    }
}
