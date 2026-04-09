<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

use App\Traits\LogsActivity;

class Employee extends Model
{
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::created(function (Employee $employee) {
            $employee->syncEmploymentHistory(
                $employee->resolvedEmploymentEffectiveFrom() ?? ($employee->created_at?->copy() ?? now())
            );
        });

        static::updated(function (Employee $employee) {
            if (! $employee->wasChanged(self::employmentHistoryTrackedFields())) {
                return;
            }

            $effectiveFrom = $employee->wasChanged('hiring_date')
                ? ($employee->resolvedEmploymentEffectiveFrom() ?? now())
                : now();

            $employee->syncEmploymentHistory($effectiveFrom);
        });
    }

    protected $fillable = [
        'full_name',
        'email',
        'employee_id',
        'designation',
        'department_id',
        'status',
        'inactive_reason',
        'hiring_date',
        'cnic', 'phone', 'gender', 'dob', 
        'current_address', 'permanent_address', 
        'father_name', 'guardian_contact',
        'education_level', 'field_of_study',
        'job_location', 'shift_start_time', 'shift_end_time', 'payroll_status', 'payment_mode',
        'profile_picture', 'cnic_front_path', 'cnic_back_path', 'cv_path', 'transcript_path',
        'bank_id', 'bank_account_title', 'bank_account_number', 'bank_name', 'bank_code', 'iban',
        'current_salary', 'last_increment',
        'hr_comments', 'banking_comments',
        'signature_path', 'onboarding_token', 'onboarding_completed_at', 'policy_accepted_at'
    ];

    protected $casts = [
        'dob' => 'date',
        'hiring_date' => 'date',
        'onboarding_completed_at' => 'datetime',
        'policy_accepted_at' => 'datetime',
        'current_salary' => 'decimal:2',
        'last_increment' => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class)->latest();
    }

    public function hrLetters()
    {
        return $this->hasMany(HrLetter::class)->latest('generated_at');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class)->latest('attendance_date');
    }

    public function employmentHistories()
    {
        return $this->hasMany(EmployeeEmploymentHistory::class)->orderByDesc('effective_from');
    }

    public function payrollRecords()
    {
        return $this->hasMany(EmployeePayrollRecord::class)->latest();
    }

    public function securityFundSnapshots()
    {
        return $this->hasMany(EmployeeSecurityFundSnapshot::class)->latest('snapshot_month');
    }

    public static function employmentHistoryTrackedFields(): array
    {
        return ['designation', 'department_id', 'payroll_status', 'job_location', 'status'];
    }

    public function syncEmploymentHistory(?Carbon $effectiveFrom = null): void
    {
        $effectiveFrom = $effectiveFrom ?? now();
        $hasActiveEmployment = $this->hasActiveEmploymentTimeline();

        $snapshot = [
            'department_id' => $this->department_id,
            'designation' => $this->designation,
            'payroll_status' => $this->payroll_status,
            'job_location' => $this->job_location,
            'employment_status' => $this->status,
        ];

        $activeHistory = $this->employmentHistories()
            ->whereNull('effective_to')
            ->latest('effective_from')
            ->first();

        if (! $hasActiveEmployment && ! $activeHistory) {
            return;
        }

        if ($activeHistory && ! $this->employmentSnapshotChanged($activeHistory, $snapshot)) {
            return;
        }

        if ($activeHistory) {
            $effectiveTo = ! $hasActiveEmployment
                ? $effectiveFrom->copy()
                : $effectiveFrom->copy()->subSecond();

            if ($effectiveTo->lt($activeHistory->effective_from)) {
                $effectiveTo = $activeHistory->effective_from->copy();
            }

            $activeHistory->update(['effective_to' => $effectiveTo]);
        }

        if (! $hasActiveEmployment) {
            return;
        }

        $this->employmentHistories()->create(array_merge($snapshot, [
            'effective_from' => $effectiveFrom,
        ]));
    }

    protected function employmentSnapshotChanged(EmployeeEmploymentHistory $history, array $snapshot): bool
    {
        foreach ($snapshot as $field => $value) {
            if ($history->{$field} !== $value) {
                return true;
            }
        }

        return false;
    }

    protected function resolvedEmploymentEffectiveFrom(): ?Carbon
    {
        $hiringDate = $this->getRawOriginal('hiring_date') ?: $this->hiring_date;

        if (! $hiringDate) {
            return null;
        }

        return Carbon::parse($hiringDate)->startOfDay();
    }

    protected function hasActiveEmploymentTimeline(): bool
    {
        return $this->status === 'active';
    }

    protected function getActivityDescription($action)
    {
        return match ($action) {
            'created' => "Employee {$this->full_name} was created",
            'updated' => $this->updatedEmployeeActivityDescription(),
            'deleted' => "Employee {$this->full_name} was deleted",
            default => "Employee {$this->full_name} was {$action}",
        };
    }

    protected function updatedEmployeeActivityDescription(): string
    {
        if ($this->wasChanged('designation')) {
            return "Employee {$this->full_name} position changed to {$this->designation}";
        }

        if ($this->wasChanged('payroll_status')) {
            return "Employee {$this->full_name} payroll status changed to {$this->payroll_status}";
        }

        if ($this->wasChanged('status')) {
            if ($this->status === 'inactive' && $this->inactive_reason) {
                return "Employee {$this->full_name} status changed to inactive. Reason: {$this->inactive_reason}";
            }

            return "Employee {$this->full_name} status changed to {$this->status}";
        }

        if ($this->wasChanged('department_id')) {
            $departmentName = $this->department?->name ?? 'Unassigned';

            return "Employee {$this->full_name} department changed to {$departmentName}";
        }

        return "Employee {$this->full_name} was updated";
    }
}
