<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'requested_by_user_id',
        'reviewed_by_user_id',
        'start_date',
        'end_date',
        'days_count',
        'status',
        'reason',
        'reviewer_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    protected function getActivityDescription($action)
    {
        $employeeName = $this->employee?->full_name ?? 'employee';
        $leaveType = $this->leaveType?->name ?? 'leave';

        return match ($action) {
            'created' => "{$employeeName} submitted a {$leaveType} request",
            'updated' => $this->leaveRequestUpdatedDescription($employeeName, $leaveType),
            'deleted' => "{$employeeName} leave request was deleted",
            default => "{$employeeName} leave request was {$action}",
        };
    }

    protected function leaveRequestUpdatedDescription(string $employeeName, string $leaveType): string
    {
        if ($this->wasChanged('status')) {
            return "{$employeeName} leave request was {$this->status}";
        }

        return "{$employeeName} {$leaveType} request was updated";
    }
}
