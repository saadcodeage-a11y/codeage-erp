<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Services\PayrollCalculationService;
use App\Services\PayslipPdfService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PayrollController extends Controller
{
    public function index(Request $request, PayrollCalculationService $payrollCalculationService)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $previewRows = $payrollCalculationService->previewMonth($month);

        $runs = PayrollRun::query()
            ->with(['generatedBy'])
            ->withCount('records')
            ->orderByDesc('pay_period_month')
            ->take(12)
            ->get();

        $selectedRun = $request->filled('run')
            ? PayrollRun::query()->with(['generatedBy'])->find($request->integer('run'))
            : PayrollRun::query()
                ->with(['generatedBy'])
                ->whereDate('pay_period_month', \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString())
                ->first();

        $previewRowsPagination = $this->paginateCollection(
            $previewRows,
            8,
            'page',
            $request->integer('page')
        );

        $selectedRunRecords = $selectedRun
            ? $selectedRun->records()
                ->with('employee')
                ->whereHas('employee')
                ->join('employees', 'employee_payroll_records.employee_id', '=', 'employees.id')
                ->orderByRaw("CASE WHEN employees.employee_id IS NULL OR employees.employee_id = '' THEN 1 ELSE 0 END")
                ->orderByRaw('LENGTH(employees.employee_id)')
                ->orderBy('employees.employee_id')
                ->select('employee_payroll_records.*')
                ->paginate(8, ['*'], 'run_page')
                ->withQueryString()
            : null;

        $totals = [
            'gross_salary' => round($previewRows->sum('gross_salary'), 2),
            'income_tax' => round($previewRows->sum('income_tax'), 2),
            'net_salary' => round($previewRows->sum('net_salary'), 2),
        ];

        return view('payroll.index', compact(
            'month',
            'previewRows',
            'previewRowsPagination',
            'runs',
            'selectedRun',
            'selectedRunRecords',
            'totals'
        ));
    }

    public function updateAdjustments(Request $request, PayrollCalculationService $payrollCalculationService)
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'adjustments' => 'nullable|array',
            'adjustments.*.incentives_bonus' => 'nullable|numeric',
            'adjustments.*.punctuality_bonus' => 'nullable|numeric',
            'adjustments.*.attendance_penalty' => 'nullable|numeric',
            'adjustments.*.arrears_adjustment' => 'nullable|numeric',
            'adjustments.*.other_adjustment' => 'nullable|numeric',
            'adjustments.*.remarks' => 'nullable|string|max:1000',
        ]);

        $payrollCalculationService->saveAdjustments(
            $validated['month'],
            $validated['adjustments'] ?? []
        );

        $redirectParams = ['month' => $validated['month']];

        if ($request->integer('page', 1) > 1) {
            $redirectParams['page'] = $request->integer('page');
        }

        if ($request->integer('run_page', 1) > 1) {
            $redirectParams['run_page'] = $request->integer('run_page');
        }

        if ($request->filled('run')) {
            $redirectParams['run'] = $request->integer('run');
        }

        return redirect()
            ->route('payroll.index', $redirectParams)
            ->with('success', 'Payroll adjustments saved successfully.');
    }

    public function autosaveAdjustment(Request $request, PayrollCalculationService $payrollCalculationService)
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'employee_id' => 'required|exists:employees,id',
            'adjustment.incentives_bonus' => 'nullable|numeric',
            'adjustment.punctuality_bonus' => 'nullable|numeric',
            'adjustment.attendance_penalty' => 'nullable|numeric',
            'adjustment.arrears_adjustment' => 'nullable|numeric',
            'adjustment.other_adjustment' => 'nullable|numeric',
            'adjustment.remarks' => 'nullable|string|max:1000',
        ]);

        $employeeId = (int) $validated['employee_id'];

        $payrollCalculationService->saveAdjustments(
            $validated['month'],
            [$employeeId => $validated['adjustment'] ?? []]
        );

        $employee = Employee::query()->findOrFail($employeeId);
        $row = $payrollCalculationService->calculateEmployeePayroll($employee, $validated['month']);

        return response()->json([
            'success' => true,
            'message' => 'Payroll adjustment saved.',
            'saved_at' => now()->toIso8601String(),
            'summary' => [
                'gross_salary' => number_format($row['gross_salary'], 2, '.', ''),
                'income_tax' => number_format($row['income_tax'], 2, '.', ''),
                'net_salary' => number_format($row['net_salary'], 2, '.', ''),
            ],
        ]);
    }

    public function generate(Request $request, PayrollCalculationService $payrollCalculationService)
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $payrollRun = $payrollCalculationService->generateRun(
            $validated['month'],
            $validated['payment_date'] ?? null,
            $request->user(),
            $validated['notes'] ?? null
        );

        return redirect()
            ->route('payroll.index', ['month' => $validated['month'], 'run' => $payrollRun->id])
            ->with('success', 'Payroll run generated successfully.');
    }

    public function finalize(PayrollRun $payrollRun, Request $request, PayrollCalculationService $payrollCalculationService)
    {
        $payrollCalculationService->finalizeRun($payrollRun);

        return redirect()
            ->route('payroll.index', [
                'month' => optional($payrollRun->pay_period_month)->format('Y-m'),
                'run' => $payrollRun->id,
            ])
            ->with('success', 'Payroll run finalized successfully.');
    }

    public function downloadPayslip(PayrollRun $payrollRun, Employee $employee, PayslipPdfService $payslipPdfService)
    {
        $payrollRecord = $payrollRun->records()
            ->where('employee_id', $employee->id)
            ->with(['employee.department', 'payrollRun'])
            ->firstOrFail();

        return $payslipPdfService->download($payrollRecord);
    }

    protected function paginateCollection(Collection $items, int $perPage, string $pageName, ?int $currentPage = null): LengthAwarePaginator
    {
        $currentPage = $currentPage ?: LengthAwarePaginator::resolveCurrentPage($pageName);
        $currentItems = $items->forPage($currentPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ]
        );
    }
}
