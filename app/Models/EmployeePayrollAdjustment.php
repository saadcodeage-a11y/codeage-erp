<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayrollAdjustment extends Model
{
    protected $fillable = [
        'employee_id',
        'adjustment_month',
        'incentives_bonus',
        'punctuality_bonus',
        'security_deduction',
        'attendance_penalty',
        'arrears_adjustment',
        'other_adjustment',
        'remarks',
    ];

    protected $casts = [
        'adjustment_month' => 'date',
        'incentives_bonus' => 'decimal:2',
        'punctuality_bonus' => 'decimal:2',
        'security_deduction' => 'decimal:2',
        'attendance_penalty' => 'decimal:2',
        'arrears_adjustment' => 'decimal:2',
        'other_adjustment' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
