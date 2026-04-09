<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceImportError extends Model
{
    protected $fillable = [
        'attendance_import_id',
        'row_number',
        'employee_code',
        'employee_name',
        'attendance_date',
        'reason',
        'row_payload',
    ];

    protected $casts = [
        'row_payload' => 'array',
    ];

    public function attendanceImport()
    {
        return $this->belongsTo(AttendanceImport::class);
    }
}
