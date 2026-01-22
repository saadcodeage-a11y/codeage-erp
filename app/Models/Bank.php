<?php

namespace App\Models;

use App\Traits\LogsActivity;

class Bank extends Model
{
    use LogsActivity;
    protected $fillable = ['name', 'code', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
