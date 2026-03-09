<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = [
        'role_id',
        'module',
        'can_read',
        'can_create',
        'can_edit',
    ];

    protected $casts = [
        'can_read' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
