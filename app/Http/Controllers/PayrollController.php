<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Services\PayrollCalculationService;
use App\Services\PayslipPdfService;
use Illuminate\Http\Request;

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
            ? PayrollRun::query()->with(['generatedBy', 'records.employee'])->find($request->integer('run'))
            : PayrollRun::query()
                ->with(['generatedBy', 'records.employee'])
                ->whereDate('pay_period_month', \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString())
                ->first();

        $totals = [
            'gross_salary' => round($previewRows->sum('gross_salary'), 2),
            'income_tax' => round($previewRows->sum('income_tax'), 2),
            'net_salary' => round($previewRows->sum('net_salary'), 2),
        ];

        return view('payroll.index', compact('month', 'previewRows', 'runs', 'selectedRun', 'totals'));
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

        return redirect()
            ->route('payroll.index', ['month' => $validated['month']])
            ->with('success', 'Payroll adjustments saved successfully.');
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
}
