<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class FranchiseEnquiry extends Model
{
    use SoftDeletes, LogsActivity, HasCreatorUpdater;

    protected $table = 'franchise_enquiries';

    protected $fillable = [
        'full_name',
        'mobile',
        'email',
        'city',
        'state',
        'investment_budget',
        'business_experience',
        'current_business',
        'message',
        'status',
        'assigned_to',
        'followup_date',
        'remarks',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
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
