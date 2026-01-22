<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class EmailTemplate extends Model
{
    use LogsActivity;
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
