<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamPerformanceReview extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'manager_user_id',
        'review_month',
        'rating',
        'feedback',
    ];

    protected $casts = [
        'review_month' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    protected function getActivityDescription($action)
    {
        $employeeName = $this->employee?->full_name ?? 'employee';
        $reviewMonth = optional($this->review_month)->format('F Y') ?? 'selected month';

        return match ($action) {
            'created' => "Performance review created for {$employeeName} ({$reviewMonth})",
            'updated' => "Performance review updated for {$employeeName} ({$reviewMonth})",
            'deleted' => "Performance review deleted for {$employeeName} ({$reviewMonth})",
            default => "Performance review {$action} for {$employeeName}",
        };
    }
}
