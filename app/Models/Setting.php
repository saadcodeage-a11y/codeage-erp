<?php

namespace App\Models;

use App\Traits\LogsActivity;

class Setting extends Model
{
    use LogsActivity;
    protected $fillable = ['key', 'value'];
}
