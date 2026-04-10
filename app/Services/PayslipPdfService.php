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
        return response($this->output($payrollRecord), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($payrollRecord) . '"',
        ]);
    }

    public function output(EmployeePayrollRecord $payrollRecord): string
    {
        return $this->makePdf($payrollRecord)->output();
    }

    public function filename(EmployeePayrollRecord $payrollRecord): string
    {
        $payrollRecord->loadMissing(['employee.department', 'payrollRun']);

        $employeeSlug = Str::slug($payrollRecord->employee->full_name ?: 'employee');
        $periodSlug = optional($payrollRecord->payrollRun->pay_period_month)->format('F-Y') ?? 'pay-period';

        return strtolower($employeeSlug . '-' . $periodSlug . '.pdf');
    }

    protected function makePdf(EmployeePayrollRecord $payrollRecord)
    {
        $payrollRecord->loadMissing(['employee.department', 'payrollRun']);

        return Pdf::loadView('payroll.payslip', [
            'record' => $payrollRecord,
            'employee' => $payrollRecord->employee,
            'payrollRun' => $payrollRecord->payrollRun,
        ])->setPaper('a4');
    }
}
