<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'full_name',
        'emp_code',
        'slug',
        'father_name',
        'mother_name',
        'nominee_name',
        'dob',
        'gender',
        'marital_status',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'department',
        'designation',
        'joining_date',
        'salary',
        'bank_name',
        'account_number',
        'ifsc_code',
        'pan_number',
        'aadhar_number',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessors for UI Compatibility
     * These allow $staff->email, $staff->phone, etc. to work in views 
     * even though they are now stored in the users table.
     */
    public function getEmailAttribute()
    {
        return $this->user->email ?? '';
    }

    public function getPhoneAttribute()
    {
        return $this->user->phone ?? '';
    }

    public function getRoleIdAttribute()
    {
        return $this->user->role_id ?? '';
    }

    public function getStatusAttribute($value)
    {
        // If the view checks $staff->status, return user status
        return ($this->user->status ?? 'inactive') === 'active' ? 1 : 0;
    }

    public function documents()
    {
        return $this->hasMany(StaffDocument::class, 'user_id', 'user_id');
    }
}
