<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeePayrollAdjustment;
use App\Models\EmployeePayrollRecord;
use App\Models\PayrollRun;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollCalculationService
{
    protected const DEFAULT_NON_PAID_LEAVE_GRACE_DAYS = 2;
    protected const DEFAULT_NON_PAID_LEAVE_DEDUCTION_PER_DAY = 500.00;
    protected const DEFAULT_SECURITY_DEDUCTION_AMOUNT = 1000.00;
    protected const DEFAULT_SHORT_HOURS_THRESHOLD_MINUTES = 480;
    protected const DEFAULT_PAYROLL_MONTH_DAY_BASIS = 30;

    public function previewMonth(string|Carbon $month): Collection
    {
        $monthStart = $this->normalizeMonth($month);
        $monthEnd = $monthStart->copy()->endOfMonth();

        return $this->eligibleEmployees($monthStart, $monthEnd)
            ->map(function (Employee $employee) use ($monthStart) {
                return $this->calculateEmployeePayroll($employee, $monthStart);
            })
            ->filter(fn (array $row) => $row['eligible'])
            ->values();
    }

    public function saveAdjustments(string|Carbon $month, array $adjustments): void
    {
        $monthStart = $this->normalizeMonth($month);

        foreach ($adjustments as $employeeId => $row) {
            $hasSecurityOverride = array_key_exists('security_deduction', $row);
            $payload = [
                'incentives_bonus' => $this->decimalValue($row['incentives_bonus'] ?? null),
                'punctuality_bonus' => $this->decimalValue($row['punctuality_bonus'] ?? null),
                'security_deduction' => $hasSecurityOverride ? $this->decimalValue($row['security_deduction'] ?? null) : null,
                'attendance_penalty' => $this->decimalValue($row['attendance_penalty'] ?? null),
                'arrears_adjustment' => $this->decimalValue($row['arrears_adjustment'] ?? null),
                'other_adjustment' => $this->decimalValue($row['other_adjustment'] ?? null),
                'remarks' => $this->nullableString($row['remarks'] ?? null),
            ];

            $hasMeaningfulValues = collect($payload)
                ->reject(fn ($value, $key) => $key === 'remarks')
                ->contains(fn ($value) => (float) $value !== 0.0);

            $hasRemarks = $payload['remarks'] !== null;

            if (! $hasMeaningfulValues && ! $hasRemarks && ! $hasSecurityOverride) {
                EmployeePayrollAdjustment::query()
                    ->where('employee_id', $employeeId)
                    ->whereDate('adjustment_month', $monthStart->toDateString())
                    ->delete();

                continue;
            }

            EmployeePayrollAdjustment::query()->updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'adjustment_month' => $monthStart->toDateString(),
                ],
                $payload
            );
        }
    }

    public function generateRun(string|Carbon $month, ?string $paymentDate, User $actor, ?string $notes = null): PayrollRun
    {
        $monthStart = $this->normalizeMonth($month);
        $rows = $this->previewMonth($monthStart);

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'month' => 'No payroll-eligible employees were found for the selected month.',
            ]);
        }

        return DB::transaction(function () use ($monthStart, $paymentDate, $actor, $notes, $rows) {
            $existingRun = PayrollRun::query()
                ->whereDate('pay_period_month', $monthStart->toDateString())
                ->first();

            if ($existingRun && $existingRun->status === 'finalized') {
                throw ValidationException::withMessages([
                    'month' => 'This payroll month has already been finalized and cannot be regenerated.',
                ]);
            }

            $run = $existingRun ?? new PayrollRun();

            if ($existingRun) {
                $existingRun->records()->delete();
            }

            $run->fill([
                'name' => $monthStart->format('F Y') . ' Payroll',
                'pay_period_month' => $monthStart->toDateString(),
                'payment_date' => $paymentDate ?: $monthStart->copy()->addMonth()->startOfMonth()->toDateString(),
                'email_subject' => 'Salary Slip for ' . $monthStart->format('F Y'),
                'email_body' => $this->defaultEmailBody($monthStart, $paymentDate),
                'source_workbook' => 'system-calculated',
                'status' => 'draft',
                'generated_by' => $actor->id,
                'generated_at' => now(),
                'finalized_at' => null,
                'notes' => $this->nullableString($notes),
            ]);
            $run->save();

            foreach ($rows as $row) {
                EmployeePayrollRecord::query()->create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $row['employee']->id,
                    'bank_code' => $row['bank_code'],
                    'beneficiary_name' => $row['beneficiary_name'],
                    'beneficiary_account_no' => $row['beneficiary_account_no'],
                    'contact_number' => $row['contact_number'],
                    'email_address' => $row['email_address'],
                    'days_absent' => $row['days_absent'],
                    'late_count' => $row['late_count'],
                    'late_absent_equivalent' => $row['late_absent_equivalent'],
                    'unpaid_leave_days' => $row['unpaid_leave_days'],
                    'short_hours_days' => $row['short_hours_days'],
                    'basic_salary' => $row['basic_salary'],
                    'last_increment' => $row['last_increment'],
                    'incentives_bonus' => $row['incentives_bonus'],
                    'punctuality_bonus' => $row['punctuality_bonus'],
                    'positive_arrears' => $row['positive_arrears'],
                    'positive_other' => $row['positive_other'],
                    'security_deduction' => $row['security_deduction'],
                    'security_total_deducted' => $row['security_total_deducted'],
                    'non_paid_leave_deduction' => $row['non_paid_leave_deduction'],
                    'attendance_penalty' => $row['attendance_penalty'],
                    'arrears_deduction' => $row['arrears_deduction'],
                    'other_deduction' => $row['other_deduction'],
                    'gross_salary' => $row['gross_salary'],
                    'income_tax' => $row['income_tax'],
                    'annual_tax_total' => $row['annual_tax_total'],
                    'net_salary' => $row['net_salary'],
                ]);
            }

            return $run->fresh(['generatedBy', 'records.employee']);
        });
    }

    public function finalizeRun(PayrollRun $payrollRun): PayrollRun
    {
        $payrollRun->update([
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        return $payrollRun->fresh(['generatedBy', 'records.employee']);
    }

    public function calculateEmployeePayroll(Employee $employee, string|Carbon $month): array
    {
        $monthStart = $this->normalizeMonth($month);
        $monthEnd = $monthStart->copy()->endOfMonth();
        $attendanceRecords = $employee->attendanceRecords()
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();
        $adjustment = $employee->payrollAdjustments()
            ->whereDate('adjustment_month', $monthStart->toDateString())
            ->first();
        $securitySnapshot = $employee->securityFundSnapshots()
            ->whereDate('snapshot_month', '<=', $monthEnd->toDateString())
            ->latest('snapshot_month')
            ->first();

        $daysAbsent = $attendanceRecords->where('status', 'absent')->count();
        $lateCount = $attendanceRecords->where('status', 'late')->count();
        $lateAbsentEquivalent = intdiv($lateCount, 3);
        $unpaidLeaveDays = $daysAbsent + $lateAbsentEquivalent;
        $shortHoursDays = $attendanceRecords->filter(function ($record) {
            if (! $record->work_duration || in_array($record->status, ['absent', 'holiday', 'weekend'], true)) {
                return false;
            }

            return $this->durationToMinutes($record->work_duration) < $this->shortHoursThresholdMinutes();
        })->count();

        $basicSalary = $this->decimalValue($employee->current_salary);
        $lastIncrement = $this->decimalValue($employee->last_increment);
        $incentivesBonus = $this->decimalValue($adjustment?->incentives_bonus);
        $punctualityBonus = $this->decimalValue($adjustment?->punctuality_bonus);
        $securityDeduction = $adjustment && $adjustment->security_deduction !== null
            ? $this->decimalValue($adjustment->security_deduction)
            : $this->securityAmountForMonth($securitySnapshot, $monthStart);
        $attendancePenalty = $this->decimalValue($adjustment?->attendance_penalty);

        $arrearsAdjustment = $this->decimalValue($adjustment?->arrears_adjustment);
        $positiveArrears = $arrearsAdjustment > 0 ? $arrearsAdjustment : 0.0;
        $arrearsDeduction = $arrearsAdjustment < 0 ? abs($arrearsAdjustment) : 0.0;

        $otherAdjustment = $this->decimalValue($adjustment?->other_adjustment);
        $positiveOther = $otherAdjustment > 0 ? $otherAdjustment : 0.0;
        $otherDeduction = $otherAdjustment < 0 ? abs($otherAdjustment) : 0.0;

        $earningsSubtotal = $basicSalary + $lastIncrement + $incentivesBonus + $punctualityBonus + $positiveArrears + $positiveOther;
        $deductionsBeforeLeave = $securityDeduction + $attendancePenalty + $arrearsDeduction + $otherDeduction;
        $dailyRateBase = max(round($earningsSubtotal - $deductionsBeforeLeave, 2), 0.0);
        $dailyRate = round($dailyRateBase / max($this->payrollMonthDayBasis(), 1), 2);
        $nonPaidLeaveDeduction = round($dailyRate * $unpaidLeaveDays, 2);
        $securityTotalDeducted = $this->securityTotalDeducted($securitySnapshot, $monthStart, $securityDeduction);

        $grossSalary = max(round($dailyRateBase - $nonPaidLeaveDeduction, 2), 0.0);
        $taxableSalary = max(round($basicSalary + $lastIncrement, 2), 0.0);
        $incomeTax = $this->incomeTax($taxableSalary);
        $annualTaxTotal = $this->cumulativeAnnualTaxTotal($employee, $monthStart, $incomeTax);
        $netSalary = max(round($grossSalary - $incomeTax, 2), 0.0);

        $eligible = $this->hasAnyPayrollValue([
            $basicSalary,
            $lastIncrement,
            $incentivesBonus,
            $punctualityBonus,
            $positiveArrears,
            $positiveOther,
            $securityDeduction,
            $nonPaidLeaveDeduction,
            $attendancePenalty,
            $arrearsDeduction,
            $otherDeduction,
            $grossSalary,
            $incomeTax,
            $annualTaxTotal,
            $netSalary,
            $daysAbsent,
            $lateCount,
            $lateAbsentEquivalent,
            $unpaidLeaveDays,
            $shortHoursDays,
        ]);

        return [
            'employee' => $employee,
            'adjustment' => $adjustment,
            'security_snapshot' => $securitySnapshot,
            'attendance_records' => $attendanceRecords,
            'month' => $monthStart,
            'eligible' => $eligible && filled($employee->employee_id),
            'basic_salary' => $basicSalary,
            'last_increment' => $lastIncrement,
            'incentives_bonus' => $incentivesBonus,
            'punctuality_bonus' => $punctualityBonus,
            'positive_arrears' => $positiveArrears,
            'positive_other' => $positiveOther,
            'security_deduction' => round($securityDeduction, 2),
            'security_total_deducted' => round($securityTotalDeducted, 2),
            'non_paid_leave_deduction' => round($nonPaidLeaveDeduction, 2),
            'attendance_penalty' => $attendancePenalty,
            'arrears_deduction' => round($arrearsDeduction, 2),
            'other_deduction' => round($otherDeduction, 2),
            'daily_rate' => $dailyRate,
            'gross_salary' => $grossSalary,
            'income_tax' => $incomeTax,
            'annual_tax_total' => $annualTaxTotal,
            'net_salary' => $netSalary,
            'days_absent' => $daysAbsent,
            'late_count' => $lateCount,
            'late_absent_equivalent' => $lateAbsentEquivalent,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'short_hours_days' => $shortHoursDays,
            'security_balance' => $this->decimalValue($securitySnapshot?->balance_in_account),
            'payment_mode' => $employee->payment_mode,
            'bank_code' => $employee->bank_code ?? $employee->bank?->code,
            'beneficiary_name' => $employee->bank_account_title ?: $employee->full_name,
            'beneficiary_account_no' => $employee->iban ?: $employee->bank_account_number,
            'contact_number' => $employee->phone,
            'email_address' => $employee->email,
            'remarks' => $adjustment?->remarks,
        ];
    }

    protected function eligibleEmployees(Carbon $monthStart, Carbon $monthEnd): EloquentCollection
    {
        return Employee::query()
            ->with(['department', 'bank'])
            ->whereNotNull('employee_id')
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->where('status', 'active')
                    ->orWhereNotNull('current_salary')
                    ->orWhereNotNull('last_increment')
                    ->orWhereHas('attendanceRecords', function ($attendanceQuery) use ($monthStart, $monthEnd) {
                        $attendanceQuery->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
                    })
                    ->orWhereHas('payrollAdjustments', function ($adjustmentQuery) use ($monthStart) {
                        $adjustmentQuery->whereDate('adjustment_month', $monthStart->toDateString());
                    });
            })
            ->orderByRaw("CASE WHEN employee_id IS NULL OR employee_id = '' THEN 1 ELSE 0 END")
            ->orderByRaw('LENGTH(employee_id)')
            ->orderBy('employee_id')
            ->get();
    }

    protected function normalizeMonth(string|Carbon $month): Carbon
    {
        if ($month instanceof Carbon) {
            return $month->copy()->startOfMonth();
        }

        return strlen($month) === 7
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : Carbon::parse($month)->startOfMonth();
    }

    protected function incomeTax(float $taxableSalary): float
    {
        $taxableSalary = max(round($taxableSalary, 2), 0.0);
        $annualizedTaxableIncome = $taxableSalary * 12;
        $annualTax = $this->annualIncomeTaxFor2025_26($annualizedTaxableIncome);

        return round($annualTax / 12, 2);
    }

    protected function nonPaidLeaveGraceDays(): int
    {
        return (int) (Setting::query()->where('key', 'payroll_non_paid_leave_grace_days')->value('value') ?? self::DEFAULT_NON_PAID_LEAVE_GRACE_DAYS);
    }

    protected function nonPaidLeaveDeductionPerDay(): float
    {
        return (float) (Setting::query()->where('key', 'payroll_non_paid_leave_deduction_per_day')->value('value') ?? self::DEFAULT_NON_PAID_LEAVE_DEDUCTION_PER_DAY);
    }

    protected function securityDeductionAmount(): float
    {
        return (float) (Setting::query()->where('key', 'payroll_security_deduction_amount')->value('value') ?? self::DEFAULT_SECURITY_DEDUCTION_AMOUNT);
    }

    protected function securityAmountForMonth($securitySnapshot, Carbon $monthStart): float
    {
        if (! $securitySnapshot) {
            return 0.0;
        }

        $field = strtolower($monthStart->format('F')) . '_amount';

        return $this->decimalValue(data_get($securitySnapshot, $field));
    }

    protected function securityTotalDeducted($securitySnapshot, Carbon $monthStart, float $resolvedMonthlyDeduction): float
    {
        if (! $securitySnapshot) {
            return 0.0;
        }

        $balanceInAccount = $this->decimalValue($securitySnapshot->balance_in_account);
        $defaultMonthlyAmount = $this->securityAmountForMonth($securitySnapshot, $monthStart);

        if ($balanceInAccount > 0) {
            return max(round($balanceInAccount - $defaultMonthlyAmount + $resolvedMonthlyDeduction, 2), 0.0);
        }

        $monthFields = [
            'july_amount',
            'august_amount',
            'september_amount',
            'october_amount',
            'november_amount',
            'december_amount',
            'january_amount',
            'february_amount',
            'march_amount',
            'april_amount',
            'may_amount',
            'june_amount',
        ];

        $total = $this->decimalValue($securitySnapshot->opening_arrears);

        foreach ($monthFields as $field) {
            $total += $this->decimalValue(data_get($securitySnapshot, $field));
        }

        $total -= $this->decimalValue($securitySnapshot->paid_amount);

        return max(round($total - $defaultMonthlyAmount + $resolvedMonthlyDeduction, 2), 0.0);
    }

    protected function shortHoursThresholdMinutes(): int
    {
        return (int) (Setting::query()->where('key', 'payroll_short_hours_threshold_minutes')->value('value') ?? self::DEFAULT_SHORT_HOURS_THRESHOLD_MINUTES);
    }

    protected function payrollMonthDayBasis(): int
    {
        return (int) (Setting::query()->where('key', 'payroll_month_day_basis')->value('value') ?? self::DEFAULT_PAYROLL_MONTH_DAY_BASIS);
    }

    protected function annualIncomeTaxFor2025_26(float $annualizedTaxableIncome): float
    {
        $annualizedTaxableIncome = max(round($annualizedTaxableIncome, 2), 0.0);

        if ($annualizedTaxableIncome <= 600000) {
            return 0.0;
        }

        if ($annualizedTaxableIncome <= 1200000) {
            return round(($annualizedTaxableIncome - 600000) * 0.025, 2);
        }

        if ($annualizedTaxableIncome <= 2200000) {
            return round((($annualizedTaxableIncome - 1200000) * 0.11) + 6000, 2);
        }

        if ($annualizedTaxableIncome <= 3200000) {
            return round((($annualizedTaxableIncome - 2200000) * 0.23) + 116000, 2);
        }

        if ($annualizedTaxableIncome <= 4100000) {
            return round((($annualizedTaxableIncome - 3200000) * 0.30) + 346000, 2);
        }

        return round((($annualizedTaxableIncome - 4100000) * 0.35) + 616000, 2);
    }

    protected function cumulativeAnnualTaxTotal(Employee $employee, Carbon $monthStart, float $currentMonthTax): float
    {
        [$fiscalYearStart, $currentMonthEnd] = $this->fiscalYearBoundsForMonth($monthStart);

        $priorTax = EmployeePayrollRecord::query()
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', function ($query) use ($fiscalYearStart, $monthStart) {
                $query->whereDate('pay_period_month', '>=', $fiscalYearStart->toDateString())
                    ->whereDate('pay_period_month', '<', $monthStart->toDateString());
            })
            ->sum('income_tax');

        return round($priorTax + $currentMonthTax, 2);
    }

    protected function fiscalYearBoundsForMonth(Carbon $monthStart): array
    {
        $fiscalYearStart = $monthStart->month >= 7
            ? $monthStart->copy()->month(7)->startOfMonth()
            : $monthStart->copy()->subYear()->month(7)->startOfMonth();

        return [$fiscalYearStart, $fiscalYearStart->copy()->addYear()->subDay()->endOfDay()];
    }

    protected function defaultEmailBody(Carbon $monthStart, ?string $paymentDate): string
    {
        $resolvedPaymentDate = $paymentDate
            ? Carbon::parse($paymentDate)
            : $monthStart->copy()->addMonth()->startOfMonth();

        return "Your salary for the month of {$monthStart->format('F Y')} has been successfully deposited via bank transfer on {$resolvedPaymentDate->format('j-m-Y')}.\n\nPlease find your salary slip attached for your reference.\n\nIf you have any questions or notice any discrepancies, feel free to reply to this email.\n\nBest Regards,\nCodeAge Pvt Ltd";
    }

    protected function durationToMinutes(string $duration): int
    {
        [$hours, $minutes] = array_pad(explode(':', $duration), 2, 0);

        return ((int) $hours * 60) + (int) $minutes;
    }

    protected function decimalValue(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    protected function hasAnyPayrollValue(array $values): bool
    {
        foreach ($values as $value) {
            if ((float) $value !== 0.0) {
                return true;
            }
        }

        return false;
    }
}
