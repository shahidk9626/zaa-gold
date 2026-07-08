<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'module_name',
        'record_id',
        'action_type',
        'old_data',
        'new_data',
        'description',
        'created_by_id',
        'ip_address',
        'browser',
        'user_agent',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
