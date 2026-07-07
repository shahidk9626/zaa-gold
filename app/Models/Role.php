<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'created_by',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withPivot('allowed');
    }
}
