<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class Kyc extends Model
{
    use LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'user_id',
        'document_type',
        'document_number',
        'front_image',
        'back_image',
        'selfie',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
