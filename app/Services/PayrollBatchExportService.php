<?php

namespace App\Services;

use App\Models\EmployeePayrollRecord;
use App\Models\PayrollRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PayrollBatchExportService
{
    public function __construct(
        protected PayslipPdfService $payslipPdfService
    ) {
    }

    public function downloadPayslipZip(PayrollRun $payrollRun): BinaryFileResponse
    {
        $records = $this->orderedRecords($payrollRun);
        $zipPath = $this->temporaryPath('payroll-payslips', 'zip');
        $zip = new ZipArchive();

        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($records as $record) {
            $zip->addFromString(
                $this->payslipPdfService->filename($record),
                $this->payslipPdfService->output($record)
            );
        }

        $zip->close();

        return response()->download(
            $zipPath,
            Str::slug($payrollRun->name) . '-payslips.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    public function downloadIftWorkbook(PayrollRun $payrollRun): BinaryFileResponse
    {
        $records = $this->orderedRecords($payrollRun)
            ->filter(fn (EmployeePayrollRecord $record) => $this->hasBankAccount($record) && $this->isFaysalRecord($record))
            ->values();

        return $this->downloadWorkbook(
            $this->buildIftSpreadsheet($payrollRun, $records),
            'ift-' . optional($payrollRun->pay_period_month)->format('F-Y') . '.xlsx'
        );
    }

    public function downloadIbftWorkbook(PayrollRun $payrollRun): BinaryFileResponse
    {
        $records = $this->orderedRecords($payrollRun)
            ->filter(fn (EmployeePayrollRecord $record) => $this->hasBankAccount($record) && ! $this->isFaysalRecord($record))
            ->values();

        return $this->downloadWorkbook(
            $this->buildIbftSpreadsheet($payrollRun, $records),
            'ibft-' . optional($payrollRun->pay_period_month)->format('F-Y') . '.xlsx'
        );
    }

    public function iftEligibleCount(PayrollRun $payrollRun): int
    {
        return $this->orderedRecords($payrollRun)
            ->filter(fn (EmployeePayrollRecord $record) => $this->hasBankAccount($record) && $this->isFaysalRecord($record))
            ->count();
    }

    public function ibftEligibleCount(PayrollRun $payrollRun): int
    {
        return $this->orderedRecords($payrollRun)
            ->filter(fn (EmployeePayrollRecord $record) => $this->hasBankAccount($record) && ! $this->isFaysalRecord($record))
            ->count();
    }

    protected function orderedRecords(PayrollRun $payrollRun): Collection
    {
        return $payrollRun->records()
            ->with(['employee.bank'])
            ->whereHas('employee')
            ->join('employees', 'employee_payroll_records.employee_id', '=', 'employees.id')
            ->orderByRaw("CASE WHEN employees.employee_id IS NULL OR employees.employee_id = '' THEN 1 ELSE 0 END")
            ->orderByRaw('LENGTH(employees.employee_id)')
            ->orderBy('employees.employee_id')
            ->select('employee_payroll_records.*')
            ->get();
    }

    protected function buildIftSpreadsheet(PayrollRun $payrollRun, Collection $records): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('IFT');
        $sheet->fromArray([
            'Beneficiary First Name',
            'Beneficiary Account No',
            'Transaction Amount',
            'Reference # 1',
            'Beneficiary Account Title',
            'Beneficiary Contact No',
            'Beneficiary Email Address',
        ], null, 'A1');

        $row = 2;
        foreach ($records as $record) {
            $sheet->fromArray([
                $record->employee?->full_name ?? $record->beneficiary_name,
                $record->beneficiary_account_no,
                (float) $record->net_salary,
                $this->salaryReference($payrollRun),
                $record->beneficiary_name,
                $record->contact_number,
                $record->email_address,
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    protected function buildIbftSpreadsheet(PayrollRun $payrollRun, Collection $records): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('IBFT');
        $sheet->fromArray([
            'Beneficiary First Name',
            'Beneficiary Account No',
            'Bank',
            'Transaction Amount',
            'Reference # 1',
            'Reference # 9',
            'Beneficiary Account Title',
            'Beneficiary Contact No',
            'Beneficiary Email Address',
        ], null, 'A1');

        $row = 2;
        foreach ($records as $record) {
            $sheet->fromArray([
                $record->employee?->full_name ?? $record->beneficiary_name,
                $record->beneficiary_account_no,
                $this->resolvedBankCode($record),
                (float) $record->net_salary,
                $this->salaryReference($payrollRun),
                $record->employee?->full_name ?? $record->beneficiary_name,
                $record->beneficiary_name,
                $record->contact_number,
                $record->email_address,
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    protected function downloadWorkbook(Spreadsheet $spreadsheet, string $filename): BinaryFileResponse
    {
        $path = $this->temporaryPath('payroll-export', 'xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download(
            $path,
            Str::lower($filename),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    protected function temporaryPath(string $prefix, string $extension): string
    {
        $base = tempnam(sys_get_temp_dir(), $prefix);

        if ($base === false) {
            throw new \RuntimeException('Unable to create a temporary export file.');
        }

        unlink($base);

        return $base . '.' . $extension;
    }

    protected function salaryReference(PayrollRun $payrollRun): string
    {
        return optional($payrollRun->pay_period_month)->format('F-Y') . ' Salary';
    }

    protected function hasBankAccount(EmployeePayrollRecord $record): bool
    {
        return filled($record->beneficiary_account_no);
    }

    protected function isFaysalRecord(EmployeePayrollRecord $record): bool
    {
        $bankName = Str::lower((string) ($record->employee?->bank?->name ?? ''));
        $bankCode = Str::upper((string) $this->resolvedBankCode($record));
        $accountNumber = Str::upper((string) ($record->beneficiary_account_no ?? ''));

        return str_contains($bankName, 'faysal')
            || in_array($bankCode, ['FAYS', 'FBL'], true)
            || str_contains($accountNumber, 'FAYS');
    }

    protected function resolvedBankCode(EmployeePayrollRecord $record): string
    {
        return (string) ($record->bank_code
            ?: $record->employee?->bank?->code
            ?: $record->employee?->bank_code
            ?: '');
    }
}
