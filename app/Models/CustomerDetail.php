<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;
use App\Traits\HasCreatorUpdater;

class CustomerDetail extends Model
{
    use LogsActivity, HasCreatorUpdater;

    protected $fillable = [
        'user_id',
        'father_name',
        'mother_name',
        'nominee_name',
        'dob',
        'gender',
        'marital_status',
        'alternate_number',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'pan_number',
        'aadhar_number',
        'occupation',
        'annual_income',
        'bank_name',
        'account_number',
        'ifsc_code',
        'branch',
        'slug',
        'created_by_id',
        'updated_by_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(CustomerDocument::class, 'customer_detail_id');
    }
}
