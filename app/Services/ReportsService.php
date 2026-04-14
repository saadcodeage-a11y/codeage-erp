<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\EmployeePayrollRecord;
use App\Models\PayrollRun;
use App\Models\PerformanceEvaluation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportsService
{
    public const REPORT_TABS = [
        'tax' => 'Tax Reports',
        'attendance' => 'Attendance Reports',
        'payroll' => 'Payroll Reports',
        'performance' => 'Performance Analytics',
    ];

    public function tabs(): array
    {
        return self::REPORT_TABS;
    }

    public function departments(): Collection
    {
        return Department::query()->orderBy('name')->get(['id', 'name']);
    }

    public function build(string $report, array $filters = []): array
    {
        return match ($report) {
            'attendance' => $this->buildAttendanceReport($filters),
            'payroll' => $this->buildPayrollReport($filters),
            'performance' => $this->buildPerformanceReport($filters),
            default => $this->buildTaxReport($filters),
        };
    }

    public function availableFiscalYears(): array
    {
        $currentStart = $this->defaultFiscalYearStart();
        $years = collect([$currentStart]);

        $payrollMin = PayrollRun::query()->min('pay_period_month');
        $attendanceMin = AttendanceRecord::query()->min('attendance_date');
        $performanceMin = PerformanceEvaluation::query()->min('period_start');

        foreach ([$payrollMin, $attendanceMin, $performanceMin] as $date) {
            if ($date) {
                $years->push($this->fiscalYearStartFromDate(Carbon::parse($date)));
            }
        }

        $min = (int) $years->min();
        $max = max($currentStart + 1, (int) $years->max());

        return collect(range($min, $max))
            ->sortDesc()
            ->mapWithKeys(fn (int $year) => [$year => $this->fiscalYearLabel($year)])
            ->all();
    }

    protected function buildTaxReport(array $filters): array
    {
        $fiscalYear = $this->sanitizeFiscalYear($filters['fiscal_year'] ?? null);
        [$start, $end] = $this->fiscalYearBounds($fiscalYear);

        $query = EmployeePayrollRecord::query()
            ->with(['employee.department', 'payrollRun'])
            ->whereHas('payrollRun', function (Builder $builder) use ($start, $end) {
                $builder->whereBetween('pay_period_month', [$start->toDateString(), $end->toDateString()]);
            });

        $query = $this->applyEmployeeAndDepartmentFilters($query, $filters);

        $records = $query->get()
            ->sortBy([
                fn (EmployeePayrollRecord $record) => $record->employee?->employee_id ?? 'ZZZ',
                fn (EmployeePayrollRecord $record) => $record->payrollRun?->pay_period_month?->format('Y-m') ?? '9999-99',
            ])
            ->values();

        $employeeSummary = $records->groupBy('employee_id')->map(function (Collection $group) {
            $first = $group->first();
            $latest = $group->sortByDesc(fn ($record) => $record->payrollRun?->pay_period_month?->timestamp ?? 0)->first();

            return [
                'employee_id' => $first->employee?->employee_id ?? 'N/A',
                'employee_name' => $first->employee?->full_name ?? 'Unknown Employee',
                'department' => $first->employee?->department?->name ?? 'Unassigned',
                'months' => $group->count(),
                'total_tax' => (float) $group->sum('income_tax'),
                'latest_annual_tax_total' => (float) ($latest?->annual_tax_total ?? 0),
            ];
        })->values();

        $monthSummary = $records->groupBy(fn (EmployeePayrollRecord $record) => $record->payrollRun?->pay_period_month?->format('F Y') ?? 'Unknown')->map(function (Collection $group, string $label) {
            return [
                'period' => $label,
                'employee_count' => $group->pluck('employee_id')->unique()->count(),
                'tax_total' => (float) $group->sum('income_tax'),
            ];
        })->values();

        return [
            'title' => 'Tax Reports',
            'description' => 'Fiscal-year tax summaries generated from saved payroll records.',
            'filters' => [
                'fiscal_year' => $fiscalYear,
                'search' => trim((string) ($filters['search'] ?? '')),
                'department_id' => $this->sanitizeNullableInt($filters['department_id'] ?? null),
            ],
            'options' => [
                'fiscal_years' => $this->availableFiscalYears(),
            ],
            'summary_cards' => [
                ['label' => 'Fiscal Year', 'value' => $this->fiscalYearLabel($fiscalYear)],
                ['label' => 'Employees', 'value' => (string) $employeeSummary->count()],
                ['label' => 'Payroll Months', 'value' => (string) $monthSummary->count()],
                ['label' => 'Total Tax', 'value' => $this->currency($records->sum('income_tax'))],
            ],
            'table' => [
                'title' => 'Employee Tax Summary',
                'description' => 'Employee-wise yearly totals and cumulative fiscal-year tax position.',
                'columns' => ['Employee', 'Department', 'Months', 'Total Tax', 'Latest Annual Tax Total'],
                'rows' => $employeeSummary->map(fn (array $row) => [
                    "{$row['employee_name']}\n{$row['employee_id']}",
                    $row['department'],
                    (string) $row['months'],
                    $this->currency($row['total_tax']),
                    $this->currency($row['latest_annual_tax_total']),
                ])->all(),
            ],
            'sections' => [
                [
                    'title' => 'Month-wise Tax Deductions',
                    'description' => 'Tax deducted by payroll month in the selected fiscal year.',
                    'columns' => ['Payroll Month', 'Employees', 'Tax Deducted'],
                    'rows' => $monthSummary->map(fn (array $row) => [
                        $row['period'],
                        (string) $row['employee_count'],
                        $this->currency($row['tax_total']),
                    ])->all(),
                ],
            ],
            'csv' => [
                'filename' => 'tax-report-' . $fiscalYear . '.csv',
                'headers' => ['Employee ID', 'Employee', 'Department', 'Payroll Month', 'Gross Salary', 'Income Tax', 'Annual Tax Total', 'Net Salary'],
                'rows' => $records->map(fn (EmployeePayrollRecord $record) => [
                    $record->employee?->employee_id ?? '',
                    $record->employee?->full_name ?? '',
                    $record->employee?->department?->name ?? '',
                    $record->payrollRun?->pay_period_month?->format('F Y') ?? '',
                    $record->gross_salary,
                    $record->income_tax,
                    $record->annual_tax_total,
                    $record->net_salary,
                ])->all(),
            ],
            'pdf' => [
                'filename' => 'tax-report-' . $fiscalYear . '.pdf',
                'subtitle' => $this->fiscalYearLabel($fiscalYear),
                'filter_summary' => [
                    'Fiscal Year' => $this->fiscalYearLabel($fiscalYear),
                    'Department' => $this->departmentLabel($filters['department_id'] ?? null),
                    'Search' => trim((string) ($filters['search'] ?? '')) ?: 'All employees',
                ],
            ],
        ];
    }

    protected function buildAttendanceReport(array $filters): array
    {
        $month = $this->sanitizeMonth($filters['month'] ?? null);
        $selectedMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $status = $filters['status'] ?? '';

        $query = AttendanceRecord::query()
            ->with(['employee.department'])
            ->whereYear('attendance_date', (int) $selectedMonth->format('Y'))
            ->whereMonth('attendance_date', (int) $selectedMonth->format('m'));

        $query = $this->applyEmployeeAndDepartmentFilters($query, $filters);

        if ($status && in_array($status, [
            AttendanceRecord::STATUS_PRESENT,
            AttendanceRecord::STATUS_LATE,
            AttendanceRecord::STATUS_ABSENT,
            AttendanceRecord::STATUS_INCOMPLETE,
            AttendanceRecord::STATUS_EARLY_LEAVE,
            AttendanceRecord::STATUS_HOLIDAY,
            AttendanceRecord::STATUS_WEEKEND,
        ], true)) {
            $query->where('status', $status);
        }

        $records = $query->get()
            ->sortBy([
                fn (AttendanceRecord $record) => $record->employee?->employee_id ?? 'ZZZ',
                fn (AttendanceRecord $record) => $record->attendance_date?->format('Y-m-d') ?? '9999-99-99',
            ])
            ->values();

        $employeeSummary = $records->groupBy('employee_id')->map(function (Collection $group) {
            $first = $group->first();

            return [
                'employee_id' => $first->employee?->employee_id ?? 'N/A',
                'employee_name' => $first->employee?->full_name ?? 'Unknown Employee',
                'department' => $first->employee?->department?->name ?? 'Unassigned',
                'present' => $group->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
                'late' => $group->where('status', AttendanceRecord::STATUS_LATE)->count(),
                'absent' => $group->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
                'incomplete' => $group->where('status', AttendanceRecord::STATUS_INCOMPLETE)->count(),
                'holiday' => $group->where('status', AttendanceRecord::STATUS_HOLIDAY)->count(),
                'weekend' => $group->where('status', AttendanceRecord::STATUS_WEEKEND)->count(),
            ];
        })->values();

        return [
            'title' => 'Attendance Reports',
            'description' => 'Monthly attendance summaries from imported machine attendance.',
            'filters' => [
                'month' => $month,
                'search' => trim((string) ($filters['search'] ?? '')),
                'department_id' => $this->sanitizeNullableInt($filters['department_id'] ?? null),
                'status' => $status,
            ],
            'options' => [
                'attendance_statuses' => [
                    '' => 'All statuses',
                    AttendanceRecord::STATUS_PRESENT => 'Present',
                    AttendanceRecord::STATUS_LATE => 'Late',
                    AttendanceRecord::STATUS_ABSENT => 'Absent',
                    AttendanceRecord::STATUS_INCOMPLETE => 'Incomplete',
                    AttendanceRecord::STATUS_EARLY_LEAVE => 'Early Leave',
                    AttendanceRecord::STATUS_HOLIDAY => 'Holiday',
                    AttendanceRecord::STATUS_WEEKEND => 'Weekend',
                ],
            ],
            'summary_cards' => [
                ['label' => 'Month', 'value' => $selectedMonth->format('F Y')],
                ['label' => 'Records', 'value' => (string) $records->count()],
                ['label' => 'Present / Late', 'value' => $records->where('status', AttendanceRecord::STATUS_PRESENT)->count() . ' / ' . $records->where('status', AttendanceRecord::STATUS_LATE)->count()],
                ['label' => 'Absent / Incomplete', 'value' => $records->where('status', AttendanceRecord::STATUS_ABSENT)->count() . ' / ' . $records->where('status', AttendanceRecord::STATUS_INCOMPLETE)->count()],
            ],
            'table' => [
                'title' => 'Employee Attendance Summary',
                'description' => 'Employee-wise monthly attendance totals.',
                'columns' => ['Employee', 'Department', 'Present', 'Late', 'Absent', 'Incomplete', 'Holiday', 'Weekend'],
                'rows' => $employeeSummary->map(fn (array $row) => [
                    "{$row['employee_name']}\n{$row['employee_id']}",
                    $row['department'],
                    (string) $row['present'],
                    (string) $row['late'],
                    (string) $row['absent'],
                    (string) $row['incomplete'],
                    (string) $row['holiday'],
                    (string) $row['weekend'],
                ])->all(),
            ],
            'sections' => [
                [
                    'title' => 'Raw Attendance Rows',
                    'description' => 'Day-level attendance rows for the selected month and filters.',
                    'columns' => ['Date', 'Employee', 'Status', 'Clock In', 'Clock Out', 'Late', 'Early', 'Work Time'],
                    'rows' => $records->map(fn (AttendanceRecord $record) => [
                        $record->attendance_date?->format('d M Y') ?? '',
                        ($record->employee?->full_name ?? '') . ' (' . ($record->employee?->employee_id ?? 'N/A') . ')',
                        ucfirst(str_replace('_', ' ', (string) $record->status)),
                        $record->clock_in ? substr((string) $record->clock_in, 0, 5) : '—',
                        $record->clock_out ? substr((string) $record->clock_out, 0, 5) : '—',
                        $record->late_duration ?: '—',
                        $record->early_duration ?: '—',
                        $record->work_duration ?: '—',
                    ])->all(),
                ],
            ],
            'csv' => [
                'filename' => 'attendance-report-' . $month . '.csv',
                'headers' => ['Date', 'Employee ID', 'Employee', 'Department', 'Status', 'Clock In', 'Clock Out', 'Late Duration', 'Early Duration', 'Absent Duration', 'Work Duration'],
                'rows' => $records->map(fn (AttendanceRecord $record) => [
                    $record->attendance_date?->format('Y-m-d') ?? '',
                    $record->employee?->employee_id ?? '',
                    $record->employee?->full_name ?? '',
                    $record->employee?->department?->name ?? '',
                    $record->status,
                    $record->clock_in,
                    $record->clock_out,
                    $record->late_duration,
                    $record->early_duration,
                    $record->absent_duration,
                    $record->work_duration,
                ])->all(),
            ],
            'pdf' => [
                'filename' => 'attendance-report-' . $month . '.pdf',
                'subtitle' => $selectedMonth->format('F Y'),
                'filter_summary' => [
                    'Month' => $selectedMonth->format('F Y'),
                    'Department' => $this->departmentLabel($filters['department_id'] ?? null),
                    'Status' => $status ? ucfirst(str_replace('_', ' ', $status)) : 'All statuses',
                    'Search' => trim((string) ($filters['search'] ?? '')) ?: 'All employees',
                ],
            ],
        ];
    }

    protected function buildPayrollReport(array $filters): array
    {
        $month = $this->sanitizeMonth($filters['month'] ?? null);
        $fiscalYear = $this->sanitizeFiscalYear($filters['fiscal_year'] ?? null);
        $selectedMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        [$fyStart, $fyEnd] = $this->fiscalYearBounds($fiscalYear);
        $payoutStatus = $filters['payout_status'] ?? '';

        $monthlyQuery = EmployeePayrollRecord::query()
            ->with(['employee.department', 'payrollRun'])
            ->whereHas('payrollRun', function (Builder $builder) use ($selectedMonth, $payoutStatus) {
                $builder->whereDate('pay_period_month', $selectedMonth->toDateString());

                if ($payoutStatus && in_array($payoutStatus, ['draft', 'finalized'], true)) {
                    $builder->where('status', $payoutStatus);
                }
            });

        $monthlyQuery = $this->applyEmployeeAndDepartmentFilters($monthlyQuery, $filters);

        $monthlyRecords = $monthlyQuery->get()
            ->sortBy(fn (EmployeePayrollRecord $record) => $record->employee?->employee_id ?? 'ZZZ')
            ->values();

        $yearlyQuery = EmployeePayrollRecord::query()
            ->with(['employee.department', 'payrollRun'])
            ->whereHas('payrollRun', function (Builder $builder) use ($fyStart, $fyEnd, $payoutStatus) {
                $builder->whereBetween('pay_period_month', [$fyStart->toDateString(), $fyEnd->toDateString()]);

                if ($payoutStatus && in_array($payoutStatus, ['draft', 'finalized'], true)) {
                    $builder->where('status', $payoutStatus);
                }
            });

        $yearlyQuery = $this->applyEmployeeAndDepartmentFilters($yearlyQuery, $filters);
        $yearlyRecords = $yearlyQuery->get();

        $monthBreakdown = $yearlyRecords->groupBy(fn (EmployeePayrollRecord $record) => $record->payrollRun?->pay_period_month?->format('F Y') ?? 'Unknown')->map(function (Collection $group, string $period) {
            return [
                'period' => $period,
                'gross' => (float) $group->sum('gross_salary'),
                'tax' => (float) $group->sum('income_tax'),
                'net' => (float) $group->sum('net_salary'),
            ];
        })->values();

        return [
            'title' => 'Payroll Reports',
            'description' => 'Monthly payroll summaries with fiscal-year rollups from saved payout records.',
            'filters' => [
                'month' => $month,
                'fiscal_year' => $fiscalYear,
                'search' => trim((string) ($filters['search'] ?? '')),
                'department_id' => $this->sanitizeNullableInt($filters['department_id'] ?? null),
                'payout_status' => $payoutStatus,
            ],
            'options' => [
                'fiscal_years' => $this->availableFiscalYears(),
                'payout_statuses' => [
                    '' => 'All statuses',
                    'draft' => 'Draft',
                    'finalized' => 'Finalized',
                ],
            ],
            'summary_cards' => [
                ['label' => 'Payroll Month', 'value' => $selectedMonth->format('F Y')],
                ['label' => 'Gross', 'value' => $this->currency($monthlyRecords->sum('gross_salary'))],
                ['label' => 'Tax', 'value' => $this->currency($monthlyRecords->sum('income_tax'))],
                ['label' => 'Net', 'value' => $this->currency($monthlyRecords->sum('net_salary'))],
            ],
            'secondary_summary_cards' => [
                ['label' => 'Fiscal Year', 'value' => $this->fiscalYearLabel($fiscalYear)],
                ['label' => 'Year Gross', 'value' => $this->currency($yearlyRecords->sum('gross_salary'))],
                ['label' => 'Year Tax', 'value' => $this->currency($yearlyRecords->sum('income_tax'))],
                ['label' => 'Year Net', 'value' => $this->currency($yearlyRecords->sum('net_salary'))],
            ],
            'table' => [
                'title' => 'Monthly Payroll Breakdown',
                'description' => 'Employee-wise payout data for the selected month.',
                'columns' => ['Employee', 'Department', 'Run Status', 'Gross', 'Tax', 'Security', 'Unpaid Leave', 'Net'],
                'rows' => $monthlyRecords->map(fn (EmployeePayrollRecord $record) => [
                    ($record->employee?->full_name ?? '') . "\n" . ($record->employee?->employee_id ?? 'N/A'),
                    $record->employee?->department?->name ?? 'Unassigned',
                    ucfirst((string) $record->payrollRun?->status),
                    $this->currency($record->gross_salary),
                    $this->currency($record->income_tax),
                    $this->currency($record->security_deduction),
                    $this->currency($record->non_paid_leave_deduction),
                    $this->currency($record->net_salary),
                ])->all(),
            ],
            'sections' => [
                [
                    'title' => 'Fiscal Year Month Breakdown',
                    'description' => 'Monthly totals across the selected fiscal year.',
                    'columns' => ['Payroll Month', 'Gross', 'Tax', 'Net'],
                    'rows' => $monthBreakdown->map(fn (array $row) => [
                        $row['period'],
                        $this->currency($row['gross']),
                        $this->currency($row['tax']),
                        $this->currency($row['net']),
                    ])->all(),
                ],
            ],
            'csv' => [
                'filename' => 'payroll-report-' . $month . '.csv',
                'headers' => ['Employee ID', 'Employee', 'Department', 'Run Status', 'Payroll Month', 'Gross Salary', 'Income Tax', 'Security Deduction', 'Unpaid Leave Deduction', 'Net Salary'],
                'rows' => $monthlyRecords->map(fn (EmployeePayrollRecord $record) => [
                    $record->employee?->employee_id ?? '',
                    $record->employee?->full_name ?? '',
                    $record->employee?->department?->name ?? '',
                    $record->payrollRun?->status ?? '',
                    $record->payrollRun?->pay_period_month?->format('Y-m') ?? '',
                    $record->gross_salary,
                    $record->income_tax,
                    $record->security_deduction,
                    $record->non_paid_leave_deduction,
                    $record->net_salary,
                ])->all(),
            ],
            'pdf' => [
                'filename' => 'payroll-report-' . $month . '.pdf',
                'subtitle' => $selectedMonth->format('F Y'),
                'filter_summary' => [
                    'Payroll Month' => $selectedMonth->format('F Y'),
                    'Fiscal Year' => $this->fiscalYearLabel($fiscalYear),
                    'Department' => $this->departmentLabel($filters['department_id'] ?? null),
                    'Run Status' => $payoutStatus ? ucfirst($payoutStatus) : 'All statuses',
                    'Search' => trim((string) ($filters['search'] ?? '')) ?: 'All employees',
                ],
            ],
        ];
    }

    protected function buildPerformanceReport(array $filters): array
    {
        $type = $filters['type'] ?? '';
        $status = $filters['status'] ?? '';
        $startDate = $this->sanitizeDate($filters['start_date'] ?? null);
        $endDate = $this->sanitizeDate($filters['end_date'] ?? null);

        $query = PerformanceEvaluation::query()
            ->with(['employee.department', 'manager', 'hrFinalizer']);

        $query = $this->applyEmployeeAndDepartmentFilters($query, $filters);

        if ($type && in_array($type, [PerformanceEvaluation::TYPE_MONTHLY, PerformanceEvaluation::TYPE_BIANNUAL], true)) {
            $query->where('evaluation_type', $type);
        }

        if ($status && in_array($status, array_keys(PerformanceEvaluation::statuses()), true)) {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->whereDate('period_start', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('period_end', '<=', $endDate);
        }

        $evaluations = $query->get()
            ->sortByDesc(fn (PerformanceEvaluation $evaluation) => $evaluation->period_start?->timestamp ?? 0)
            ->values();

        $managerMetrics = collect([
            'Performance' => $evaluations->avg('manager_performance'),
            'Punctuality' => $evaluations->avg('manager_punctuality'),
            'Behaviour' => $evaluations->avg('manager_behaviour'),
            'Learning' => $evaluations->avg('manager_learning'),
            'Participation' => $evaluations->avg('manager_participation'),
        ]);

        $hrMetrics = collect([
            'Performance' => $evaluations->avg('hr_performance'),
            'Punctuality' => $evaluations->avg('hr_punctuality'),
            'Behaviour' => $evaluations->avg('hr_behaviour'),
            'Learning' => $evaluations->avg('hr_learning'),
            'Participation' => $evaluations->avg('hr_participation'),
        ]);

        $statusBreakdown = collect(PerformanceEvaluation::statuses())->map(function (string $label, string $key) use ($evaluations) {
            return [
                'status' => $label,
                'count' => $evaluations->where('status', $key)->count(),
            ];
        })->values();

        return [
            'title' => 'Performance Analytics',
            'description' => 'Monthly and bi-annual evaluation analytics with manager and HR scoring.',
            'filters' => [
                'type' => $type,
                'status' => $status,
                'search' => trim((string) ($filters['search'] ?? '')),
                'department_id' => $this->sanitizeNullableInt($filters['department_id'] ?? null),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'options' => [
                'types' => ['' => 'All types'] + PerformanceEvaluation::types(),
                'statuses' => ['' => 'All statuses'] + PerformanceEvaluation::statuses(),
            ],
            'summary_cards' => [
                ['label' => 'Evaluations', 'value' => (string) $evaluations->count()],
                ['label' => 'Monthly / Bi-Annual', 'value' => $evaluations->where('evaluation_type', PerformanceEvaluation::TYPE_MONTHLY)->count() . ' / ' . $evaluations->where('evaluation_type', PerformanceEvaluation::TYPE_BIANNUAL)->count()],
                ['label' => 'Manager Avg', 'value' => number_format((float) $managerMetrics->filter()->avg(), 2)],
                ['label' => 'HR Avg', 'value' => number_format((float) $hrMetrics->filter()->avg(), 2)],
            ],
            'table' => [
                'title' => 'Evaluation History',
                'description' => 'Historical performance records by employee and period.',
                'columns' => ['Employee', 'Department', 'Type', 'Period', 'Manager Score', 'Final Score', 'Status'],
                'rows' => $evaluations->map(fn (PerformanceEvaluation $evaluation) => [
                    ($evaluation->employee?->full_name ?? '') . "\n" . ($evaluation->employee?->employee_id ?? 'N/A'),
                    $evaluation->employee?->department?->name ?? 'Unassigned',
                    PerformanceEvaluation::types()[$evaluation->evaluation_type] ?? ucfirst($evaluation->evaluation_type),
                    $evaluation->periodLabel(),
                    $evaluation->managerAverage() !== null ? number_format($evaluation->managerAverage(), 2) : 'N/A',
                    $evaluation->hrAverage() !== null ? number_format($evaluation->hrAverage(), 2) : 'Pending',
                    PerformanceEvaluation::statuses()[$evaluation->status] ?? ucfirst($evaluation->status),
                ])->all(),
            ],
            'sections' => [
                [
                    'title' => 'Metric Averages',
                    'description' => 'Average metric scores across the filtered evaluations.',
                    'columns' => ['Metric', 'Manager Avg', 'HR Avg'],
                    'rows' => $managerMetrics->map(function ($managerValue, string $metric) use ($hrMetrics) {
                        return [
                            $metric,
                            $managerValue !== null ? number_format((float) $managerValue, 2) : 'N/A',
                            $hrMetrics[$metric] !== null ? number_format((float) $hrMetrics[$metric], 2) : 'N/A',
                        ];
                    })->values()->all(),
                ],
                [
                    'title' => 'Status Distribution',
                    'description' => 'Evaluation counts by workflow status.',
                    'columns' => ['Status', 'Count'],
                    'rows' => $statusBreakdown->map(fn (array $row) => [$row['status'], (string) $row['count']])->all(),
                ],
            ],
            'csv' => [
                'filename' => 'performance-report.csv',
                'headers' => [
                    'Employee ID',
                    'Employee',
                    'Department',
                    'Type',
                    'Period Start',
                    'Period End',
                    'Status',
                    'Manager Performance',
                    'Manager Punctuality',
                    'Manager Behaviour',
                    'Manager Learning',
                    'Manager Participation',
                    'HR Performance',
                    'HR Punctuality',
                    'HR Behaviour',
                    'HR Learning',
                    'HR Participation',
                ],
                'rows' => $evaluations->map(fn (PerformanceEvaluation $evaluation) => [
                    $evaluation->employee?->employee_id ?? '',
                    $evaluation->employee?->full_name ?? '',
                    $evaluation->employee?->department?->name ?? '',
                    $evaluation->evaluation_type,
                    optional($evaluation->period_start)->format('Y-m-d'),
                    optional($evaluation->period_end)->format('Y-m-d'),
                    $evaluation->status,
                    $evaluation->manager_performance,
                    $evaluation->manager_punctuality,
                    $evaluation->manager_behaviour,
                    $evaluation->manager_learning,
                    $evaluation->manager_participation,
                    $evaluation->hr_performance,
                    $evaluation->hr_punctuality,
                    $evaluation->hr_behaviour,
                    $evaluation->hr_learning,
                    $evaluation->hr_participation,
                ])->all(),
            ],
            'pdf' => [
                'filename' => 'performance-report.pdf',
                'subtitle' => 'Performance Evaluation Analytics',
                'filter_summary' => [
                    'Type' => $type ? (PerformanceEvaluation::types()[$type] ?? ucfirst($type)) : 'All types',
                    'Status' => $status ? (PerformanceEvaluation::statuses()[$status] ?? ucfirst($status)) : 'All statuses',
                    'Department' => $this->departmentLabel($filters['department_id'] ?? null),
                    'Date Range' => $startDate || $endDate ? trim(($startDate ?: 'Start') . ' to ' . ($endDate ?: 'End')) : 'All periods',
                    'Search' => trim((string) ($filters['search'] ?? '')) ?: 'All employees',
                ],
            ],
        ];
    }

    protected function applyEmployeeAndDepartmentFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $departmentId = $this->sanitizeNullableInt($filters['department_id'] ?? null);

        if ($search || $departmentId) {
            $query->whereHas('employee', function (Builder $builder) use ($search, $departmentId) {
                if ($search) {
                    $builder->where(function (Builder $nested) use ($search) {
                        $nested->where('full_name', 'like', "%{$search}%")
                            ->orWhere('employee_id', 'like', "%{$search}%")
                            ->orWhere('designation', 'like', "%{$search}%");
                    });
                }

                if ($departmentId) {
                    $builder->where('department_id', $departmentId);
                }
            });
        }

        return $query;
    }

    protected function sanitizeMonth(?string $month): string
    {
        if ($month && preg_match('/^\d{4}\-\d{2}$/', $month) === 1) {
            return $month;
        }

        return now()->format('Y-m');
    }

    protected function sanitizeFiscalYear($year): int
    {
        $year = (int) $year;

        if ($year < 2000 || $year > 2100) {
            return $this->defaultFiscalYearStart();
        }

        return $year;
    }

    protected function sanitizeNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function sanitizeDate(?string $date): ?string
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function fiscalYearBounds(int $startYear): array
    {
        $start = Carbon::create($startYear, 7, 1)->startOfDay();
        $end = $start->copy()->addYear()->subDay()->endOfDay();

        return [$start, $end];
    }

    protected function defaultFiscalYearStart(): int
    {
        return $this->fiscalYearStartFromDate(now());
    }

    protected function fiscalYearStartFromDate(Carbon $date): int
    {
        return $date->month >= 7 ? $date->year : $date->year - 1;
    }

    protected function fiscalYearLabel(int $startYear): string
    {
        return 'FY ' . $startYear . '-' . substr((string) ($startYear + 1), -2);
    }

    protected function departmentLabel($departmentId): string
    {
        $departmentId = $this->sanitizeNullableInt($departmentId);

        if (! $departmentId) {
            return 'All departments';
        }

        return Department::query()->whereKey($departmentId)->value('name') ?? 'All departments';
    }

    protected function currency($value): string
    {
        return 'PKR ' . number_format((float) $value, 2);
    }
}
