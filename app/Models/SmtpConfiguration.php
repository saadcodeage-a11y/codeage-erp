<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpConfiguration extends Model
{
    protected $fillable = [
        'name',
        'description',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_email',
        'from_name',
        'is_default',
    ];
}
