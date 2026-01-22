<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Employee extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'full_name',
        'email',
        'employee_id',
        'designation',
        'department_id',
        'status',
        'hiring_date',
        'cnic', 'phone', 'gender', 'dob', 
        'current_address', 'permanent_address', 
        'father_name', 'guardian_contact',
        'education_level', 'field_of_study',
        'job_location', 'payroll_status',
        'profile_picture', 'cnic_front_path', 'cnic_back_path', 'cv_path', 'transcript_path',
        'bank_id', 'bank_account_title', 'bank_account_number', 'bank_name', 'iban',
        'hr_comments', 'banking_comments',
        'signature_path', 'onboarding_token', 'onboarding_completed_at', 'policy_accepted_at'
    ];

    protected $casts = [
        'dob' => 'date',
        'hiring_date' => 'date',
        'onboarding_completed_at' => 'datetime',
        'policy_accepted_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
