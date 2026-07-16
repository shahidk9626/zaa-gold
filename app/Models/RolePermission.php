<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = ['role_id', 'permission_id', 'allowed'];
}
