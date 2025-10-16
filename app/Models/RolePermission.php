<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = [
        'role',
        'permission_id',
    ];

    protected $casts = [
        'role' => UserRole::class,
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
