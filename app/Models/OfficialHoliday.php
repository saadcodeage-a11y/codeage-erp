<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class OfficialHoliday extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'holiday_date',
        'description',
    ];

    protected $casts = [
        'holiday_date' => 'date',
    ];

    protected function getActivityDescription($action)
    {
        $date = $this->holiday_date?->format('d M Y') ?? 'scheduled date';

        return match ($action) {
            'created' => "Official holiday {$this->name} was added for {$date}",
            'updated' => "Official holiday {$this->name} was updated",
            'deleted' => "Official holiday {$this->name} was deleted",
            default => "Official holiday {$this->name} was {$action}",
        };
    }
}
