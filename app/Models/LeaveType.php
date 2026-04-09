<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'max_days',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function requests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    protected function getActivityDescription($action)
    {
        return match ($action) {
            'created' => "Leave type {$this->name} was created",
            'updated' => "Leave type {$this->name} was updated",
            'deleted' => "Leave type {$this->name} was deleted",
            default => "Leave type {$this->name} was {$action}",
        };
    }
}
