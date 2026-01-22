<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplate extends Model
{
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
