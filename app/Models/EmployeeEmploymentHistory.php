<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class EmployeeEmploymentHistory extends Model
{
    use LogsActivity;

    protected $fillable = [
        'employee_id',
        'department_id',
        'designation',
        'payroll_status',
        'job_location',
        'employment_status',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    protected function getActivityDescription($action)
    {
        $employeeName = $this->employee?->full_name ?? 'employee';

        return match ($action) {
            'created' => "Employment history added for {$employeeName}",
            'updated' => "Employment history updated for {$employeeName}",
            'deleted' => "Employment history removed for {$employeeName}",
            default => "Employment history {$action} for {$employeeName}",
        };
    }
}
