<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \App\Traits\LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'status',
        'phone',
        'whatsapp_number',
        'referred_by_staff_id',
        'profile_completed',
        'profile_image',
        'verification_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function staffDetail()
    {
        return $this->hasOne(StaffDetail::class);
    }

    public function staffDocuments()
    {
        return $this->hasMany(StaffDocument::class);
    }

    public function customerDetail()
    {
        return $this->hasOne(CustomerDetail::class);
    }

    public function customerDocuments()
    {
        return $this->hasManyThrough(CustomerDocument::class, CustomerDetail::class, 'user_id', 'customer_detail_id');
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by_staff_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by_staff_id');
    }

    public function customerReferral()
    {
        return $this->hasOne(CustomerReferral::class, 'customer_id');
    }

    public function staffReferrals()
    {
        return $this->hasMany(CustomerReferral::class, 'staff_id');
    }

    public function profileUpdateRequests()
    {
        return $this->hasMany(CustomerProfileUpdateRequest::class, 'customer_id');
    }

    public function hasPendingProfileUpdateRequest(): bool
    {
        return $this->profileUpdateRequests()->where('status', 'Pending')->exists();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')->withPivot('allowed');
    }

    public function bookings()
    {
        return $this->hasMany(GoldBooking::class, 'customer_id');
    }

    public function customerAddresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    public function isCustomer(): bool
    {
        return $this->role?->slug === 'customer';
    }

    public function isStaffOrAdmin(): bool
    {
        return in_array($this->role?->slug, ['super-admin', 'admin', 'staff'], true);
    }

    public function getProfileStatusAttribute(): string
    {
        return $this->profile_completed ? 'Complete' : 'Incomplete';
    }

    public function getProfileImageUrlAttribute(): string
    {
        if ($this->profile_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->profile_image)) {
            return storage_url($this->profile_image);
        }
        return asset('assets/images/faces/face1.jpg');
    }
}
