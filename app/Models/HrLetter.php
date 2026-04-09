<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class HrLetter extends Model
{
    use LogsActivity;

    protected $fillable = [
        'employee_id',
        'generated_by_user_id',
        'type',
        'title',
        'body',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    protected function getActivityDescription($action)
    {
        $employeeName = $this->employee?->full_name ?? 'employee';

        return match ($action) {
            'created' => ucfirst($this->type) . " letter generated for {$employeeName}",
            'updated' => ucfirst($this->type) . " letter updated for {$employeeName}",
            'deleted' => ucfirst($this->type) . " letter deleted for {$employeeName}",
            default => ucfirst($this->type) . " letter {$action} for {$employeeName}",
        };
    }
}
