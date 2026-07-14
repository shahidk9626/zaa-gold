<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class SellOldGoldEnquiry extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $table = 'sell_old_gold_enquiries';

    protected $fillable = [
        'customer_name',
        'mobile',
        'email',
        'city',
        'gold_type',
        'estimated_weight',
        'estimated_value',
        'remarks',
        'status',
        'assigned_to',
        'followup_date',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'estimated_weight' => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'followup_date' => 'datetime',
    ];

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
