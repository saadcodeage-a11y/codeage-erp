<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class SmtpConfiguration extends Model
{
    use LogsActivity;
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
