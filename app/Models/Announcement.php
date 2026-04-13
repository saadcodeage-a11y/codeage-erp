<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'message',
        'is_global',
        'is_active',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class)->orderBy('name');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->canAccessModule('announcements', 'create') || $user->canAccessModule('announcements', 'edit')) {
            return $query;
        }

        $departmentId = $user->employee?->department_id;

        return $query
            ->where('is_active', true)
            ->where(function (Builder $announcementQuery) use ($departmentId) {
                $announcementQuery->where('is_global', true);

                if ($departmentId) {
                    $announcementQuery->orWhereHas('departments', function (Builder $departmentQuery) use ($departmentId) {
                        $departmentQuery->where('departments.id', $departmentId);
                    });
                }
            });
    }

    protected function getActivityDescription($action)
    {
        return match ($action) {
            'created' => "Announcement \"{$this->title}\" was created",
            'updated' => "Announcement \"{$this->title}\" was updated",
            'deleted' => "Announcement \"{$this->title}\" was deleted",
            default => "Announcement \"{$this->title}\" was {$action}",
        };
    }
}
