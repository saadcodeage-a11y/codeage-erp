<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class AttendanceImport extends Model
{
    use LogsActivity;

    protected $fillable = [
        'imported_by_user_id',
        'source_file_name',
        'source_file_extension',
        'total_rows',
        'imported_rows',
        'error_rows',
        'duplicate_rows',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function importedBy()
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }

    public function errors()
    {
        return $this->hasMany(AttendanceImportError::class)->latest();
    }

    public function records()
    {
        return $this->hasMany(AttendanceRecord::class)->latest('attendance_date');
    }

    protected function getActivityDescription($action)
    {
        return match ($action) {
            'created' => "Attendance import {$this->source_file_name} was uploaded",
            'updated' => "Attendance import {$this->source_file_name} was updated",
            'deleted' => "Attendance import {$this->source_file_name} was deleted",
            default => "Attendance import {$this->source_file_name} was {$action}",
        };
    }
}
