<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    protected $fillable = [
        'name',
        'pay_period_month',
        'payment_date',
        'email_subject',
        'email_body',
        'source_workbook',
        'status',
        'generated_by',
        'generated_at',
        'finalized_at',
        'notes',
    ];

    protected $casts = [
        'pay_period_month' => 'date',
        'payment_date' => 'date',
        'generated_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function records()
    {
        return $this->hasMany(EmployeePayrollRecord::class)->latest();
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
