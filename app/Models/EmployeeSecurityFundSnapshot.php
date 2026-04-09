<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSecurityFundSnapshot extends Model
{
    protected $fillable = [
        'employee_id',
        'fiscal_year_label',
        'snapshot_month',
        'opening_arrears',
        'july_amount',
        'august_amount',
        'september_amount',
        'october_amount',
        'november_amount',
        'december_amount',
        'january_amount',
        'february_amount',
        'march_amount',
        'april_amount',
        'may_amount',
        'june_amount',
        'paid_amount',
        'balance_in_account',
        'remarks',
    ];

    protected $casts = [
        'snapshot_month' => 'date',
        'opening_arrears' => 'decimal:2',
        'july_amount' => 'decimal:2',
        'august_amount' => 'decimal:2',
        'september_amount' => 'decimal:2',
        'october_amount' => 'decimal:2',
        'november_amount' => 'decimal:2',
        'december_amount' => 'decimal:2',
        'january_amount' => 'decimal:2',
        'february_amount' => 'decimal:2',
        'march_amount' => 'decimal:2',
        'april_amount' => 'decimal:2',
        'may_amount' => 'decimal:2',
        'june_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_in_account' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
