<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity($action)
    {
        $description = $this->getActivityDescription($action);
        $properties = $this->getActivityProperties($action);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'description' => $description,
            'type' => $this->getActivityType($action),
            'subject_id' => $this->id,
            'subject_type' => get_class($this),
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    protected function getActivityDescription($action)
    {
        $name = class_basename($this);
        return "{$name} was {$action}";
    }

    protected function getActivityType($action)
    {
        return match ($action) {
            'created' => 'success',
            'deleted' => 'warning',
            default => 'info',
        };
    }

    protected function getActivityProperties($action)
    {
        if ($action === 'updated') {
            return [
                'attributes' => $this->getAttributes(),
                'old' => array_intersect_key($this->getOriginal(), $this->getChanges()),
            ];
        }

        return ['attributes' => $this->getAttributes()];
    }
}
