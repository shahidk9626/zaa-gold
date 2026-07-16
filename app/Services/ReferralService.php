<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use App\Models\GoldBooking;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    /**
     * Get paginated referrals with filters
     */
    public function getFilteredReferrals(array $filters, int $perPage = 20)
    {
        $query = Referral::with(['referrer', 'referred', 'booking'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('referral_code', 'like', '%' . $search . '%')
                  ->orWhereHas('referrer', function ($qr) use ($search) {
                      $qr->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('referred', function ($qrd) use ($search) {
                      $qrd->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('reward_status', $filters['status']);
        }

        if (!empty($filters['reward_type'])) {
            $query->where('reward_type', $filters['reward_type']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'] . ' 00:00:00', $filters['end_date'] . ' 23:59:59']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new referral
     */
    public function createReferral(array $data)
    {
        return Referral::create([
            'referral_code' => $data['referral_code'],
            'referrer_customer_id' => $data['referrer_customer_id'],
            'referred_customer_id' => $data['referred_customer_id'],
            'booking_id' => $data['booking_id'] ?? null,
            'reward_type' => $data['reward_type'] ?? 'Cash',
            'reward_amount' => $data['reward_amount'] ?? 0.00,
            'reward_status' => $data['reward_status'] ?? 'Pending',
            'remarks' => $data['remarks'] ?? null,
        ]);
    }

    /**
     * Update referral status and details
     */
    public function updateReferral(Referral $referral, array $data)
    {
        $referral->update($data);
        return $referral;
    }

    /**
     * Get eligible customers for referrals
     */
    public function getCustomers()
    {
        return User::whereHas('role', function($q) {
            $q->where('slug', 'customer');
        })->where('status', 'active')->orderBy('name')->get();
    }

    /**
     * Get bookings for a customer
     */
    public function getCustomerBookings($customerId)
    {
        return GoldBooking::where('customer_id', $customerId)->orderBy('booking_number')->get();
    }
}
