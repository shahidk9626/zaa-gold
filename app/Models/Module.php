<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['name', 'slug', 'status'];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
