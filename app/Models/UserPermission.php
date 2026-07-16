<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    use \App\Traits\LogsActivity;

    protected $fillable = ['user_id', 'permission_id', 'allowed'];
}
