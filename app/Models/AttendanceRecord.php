<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_import_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'late_duration',
        'early_duration',
        'absent_duration',
        'work_duration',
        'shift_start_time',
        'shift_end_time',
        'status',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceImport()
    {
        return $this->belongsTo(AttendanceImport::class);
    }
}
