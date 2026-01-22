<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class FormTemplate extends Model
{
    use LogsActivity;
    protected $fillable = [
        'name',
        'description',
        'fields_count',
        'required_count',
        'is_active',
        'smtp_config_id',
    ];

    public function smtpConfig()
    {
        return $this->belongsTo(SmtpConfiguration::class);
    }
}
