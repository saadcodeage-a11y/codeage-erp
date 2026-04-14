<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PerformanceEvaluation extends Model
{
    use HasFactory, LogsActivity;

    public const TYPE_MONTHLY = 'monthly';
    public const TYPE_BIANNUAL = 'biannual';

    public const STATUS_MANAGER_DRAFT = 'manager_draft';
    public const STATUS_PENDING_HR = 'pending_hr';
    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'employee_id',
        'manager_user_id',
        'evaluation_type',
        'period_start',
        'period_end',
        'status',
        'manager_performance',
        'manager_punctuality',
        'manager_behaviour',
        'manager_learning',
        'manager_participation',
        'manager_feedback',
        'manager_submitted_at',
        'hr_performance',
        'hr_punctuality',
        'hr_behaviour',
        'hr_learning',
        'hr_participation',
        'hr_feedback',
        'hr_finalized_by_user_id',
        'hr_finalized_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'manager_submitted_at' => 'datetime',
        'hr_finalized_at' => 'datetime',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_MONTHLY => 'Monthly',
            self::TYPE_BIANNUAL => 'Bi-Annual',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_MANAGER_DRAFT => 'Manager Draft',
            self::STATUS_PENDING_HR => 'Pending HR',
            self::STATUS_FINALIZED => 'Finalized',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function hrFinalizer()
    {
        return $this->belongsTo(User::class, 'hr_finalized_by_user_id');
    }

    public function managerAverage(): ?float
    {
        $metrics = collect([
            $this->manager_performance,
            $this->manager_punctuality,
            $this->manager_behaviour,
            $this->manager_learning,
            $this->manager_participation,
        ])->filter(fn ($value) => $value !== null);

        if ($metrics->isEmpty()) {
            return null;
        }

        return round($metrics->avg(), 2);
    }

    public function hrAverage(): ?float
    {
        $metrics = collect([
            $this->hr_performance,
            $this->hr_punctuality,
            $this->hr_behaviour,
            $this->hr_learning,
            $this->hr_participation,
        ])->filter(fn ($value) => $value !== null);

        if ($metrics->isEmpty()) {
            return null;
        }

        return round($metrics->avg(), 2);
    }

    public function periodLabel(): string
    {
        if ($this->evaluation_type === self::TYPE_MONTHLY) {
            return $this->period_start?->format('F Y') ?? 'Monthly';
        }

        return ($this->period_start?->format('d M Y') ?? '') . ' to ' . ($this->period_end?->format('d M Y') ?? '');
    }

    protected function getActivityDescription($action)
    {
        $employeeName = $this->employee?->full_name ?? 'employee';
        $periodLabel = $this->periodLabel();

        return match ($action) {
            'created' => "Performance evaluation created for {$employeeName} ({$periodLabel})",
            'updated' => "Performance evaluation updated for {$employeeName} ({$periodLabel})",
            'deleted' => "Performance evaluation deleted for {$employeeName} ({$periodLabel})",
            default => "Performance evaluation {$action} for {$employeeName}",
        };
    }
}
