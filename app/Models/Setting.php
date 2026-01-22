<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Setting extends Model
{
    use LogsActivity;
    protected $fillable = ['key', 'value'];
}
