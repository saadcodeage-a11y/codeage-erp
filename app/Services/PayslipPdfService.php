<?php

namespace App\Services;

use App\Models\EmployeePayrollRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PayslipPdfService
{
    public function download(EmployeePayrollRecord $payrollRecord): Response
    {
        $payrollRecord->loadMissing(['employee.department', 'payrollRun']);

        $pdf = Pdf::loadView('payroll.payslip', [
            'record' => $payrollRecord,
            'employee' => $payrollRecord->employee,
            'payrollRun' => $payrollRecord->payrollRun,
        ])->setPaper('a4');

        $employeeSlug = Str::slug($payrollRecord->employee->full_name ?: 'employee');
        $periodSlug = optional($payrollRecord->payrollRun->pay_period_month)->format('F-Y') ?? 'pay-period';

        return $pdf->download(strtolower($employeeSlug . '-' . $periodSlug . '.pdf'));
    }
}
