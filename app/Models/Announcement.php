<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory, LogsActivity;

    public const TYPE_GENERAL = 'general';
    public const TYPE_OFFICIAL_HOLIDAY = 'official_holiday';

    public const DATE_MODE_SINGLE = 'single';
    public const DATE_MODE_RANGE = 'range';

    protected $fillable = [
        'title',
        'message',
        'announcement_type',
        'date_mode',
        'event_date',
        'event_start_date',
        'event_end_date',
        'is_global',
        'is_active',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'event_date' => 'date',
        'event_start_date' => 'date',
        'event_end_date' => 'date',
        'published_at' => 'datetime',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_GENERAL => 'General Announcement',
            self::TYPE_OFFICIAL_HOLIDAY => 'Official Holiday',
        ];
    }

    public static function dateModes(): array
    {
        return [
            self::DATE_MODE_SINGLE => 'Single Date',
            self::DATE_MODE_RANGE => 'Date Range',
        ];
    }

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

    public function audienceLabel(): string
    {
        return $this->is_global ? 'Global' : 'Department';
    }

    public function eventDateLabel(): ?string
    {
        if ($this->announcement_type !== self::TYPE_OFFICIAL_HOLIDAY) {
            return null;
        }

        if ($this->date_mode === self::DATE_MODE_RANGE && $this->event_start_date && $this->event_end_date) {
            return $this->event_start_date->format('d M Y') . ' to ' . $this->event_end_date->format('d M Y');
        }

        return $this->event_date?->format('d M Y');
    }
}
