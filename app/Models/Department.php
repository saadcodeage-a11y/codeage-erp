<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\LogsActivity;

class Department extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'total_employees'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
