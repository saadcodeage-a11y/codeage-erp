<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'category',
        'name',
        'description',
        'subject',
        'body',
        'variables',
        'smtp_config_id',
        'is_active',
    ];

    public function smtpConfig()
    {
        return $this->belongsTo(SmtpConfiguration::class, 'smtp_config_id');
    }
}
