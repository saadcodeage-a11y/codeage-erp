<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayrollRecord extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'bank_code',
        'beneficiary_name',
        'beneficiary_account_no',
        'contact_number',
        'email_address',
        'days_absent',
        'short_hours_days',
        'basic_salary',
        'last_increment',
        'incentives_bonus',
        'punctuality_bonus',
        'positive_arrears',
        'positive_other',
        'security_deduction',
        'security_total_deducted',
        'non_paid_leave_deduction',
        'attendance_penalty',
        'arrears_deduction',
        'other_deduction',
        'gross_salary',
        'income_tax',
        'net_salary',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'last_increment' => 'decimal:2',
        'incentives_bonus' => 'decimal:2',
        'punctuality_bonus' => 'decimal:2',
        'positive_arrears' => 'decimal:2',
        'positive_other' => 'decimal:2',
        'security_deduction' => 'decimal:2',
        'security_total_deducted' => 'decimal:2',
        'non_paid_leave_deduction' => 'decimal:2',
        'attendance_penalty' => 'decimal:2',
        'arrears_deduction' => 'decimal:2',
        'other_deduction' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'income_tax' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }
}
