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
    ];

    protected $casts = [
        'pay_period_month' => 'date',
        'payment_date' => 'date',
    ];

    public function records()
    {
        return $this->hasMany(EmployeePayrollRecord::class)->latest();
    }
}
