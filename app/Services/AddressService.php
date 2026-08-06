<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class AddressService
{
    public function listForCustomer(int $customerId): Collection
    {
        $this->seedFromCustomerDetailIfMissing($customerId);

        return CustomerAddress::where('customer_id', $customerId)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();
    }

    public function createForCustomer(int $customerId, array $data): CustomerAddress
    {
        return DB::transaction(function () use ($customerId, $data) {
            $hasAddress = CustomerAddress::where('customer_id', $customerId)->exists();
            $isDefault = (bool)($data['is_default'] ?? !$hasAddress);

            if ($isDefault) {
                CustomerAddress::where('customer_id', $customerId)->update(['is_default' => false]);
            }

            $address = CustomerAddress::create([
                'customer_id' => $customerId,
                'address_name' => $data['address_name'],
                'mobile' => $data['mobile'],
                'alternate_mobile' => $data['alternate_mobile'] ?? null,
                'house_no' => $data['house_no'] ?? null,
                'street' => $data['street'] ?? null,
                'area' => $data['area'] ?? null,
                'landmark' => $data['landmark'] ?? null,
                'city' => $data['city'],
                'state' => $data['state'],
                'pin_code' => $data['pin_code'],
                'country' => $data['country'] ?? 'India',
                'address_type' => $data['address_type'] ?? 'Home',
                'is_default' => $isDefault,
                'created_by_id' => Auth::id() ?? $customerId,
            ]);

            $this->logActivity('address_added', "Delivery address {$address->address_name} added.", $customerId);

            return $address;
        });
    }

    public function findForCustomer(int $customerId, int $addressId): CustomerAddress
    {
        $this->seedFromCustomerDetailIfMissing($customerId);

        return CustomerAddress::where('customer_id', $customerId)->findOrFail($addressId);
    }

    public function snapshot(CustomerAddress $address): array
    {
        return [
            'customer_address_id' => $address->id,
            'delivery_address' => $address->full_address,
            'delivery_address_name' => $address->address_name,
            'delivery_address_mobile' => $address->mobile,
            'delivery_address_alternate_mobile' => $address->alternate_mobile,
            'delivery_address_type' => $address->address_type,
            'delivery_city' => $address->city,
            'delivery_state' => $address->state,
            'delivery_pin_code' => $address->pin_code,
            'delivery_country' => $address->country,
        ];
    }

    protected function seedFromCustomerDetailIfMissing(int $customerId): void
    {
        if (CustomerAddress::where('customer_id', $customerId)->exists()) {
            return;
        }

        $customer = User::with('customerDetail')->find($customerId);
        $detail = $customer?->customerDetail;

        if (!$detail || empty($detail->address)) {
            return;
        }

        $this->createForCustomer($customerId, [
            'address_name' => $customer->name ?? 'Primary Address',
            'mobile' => $customer->phone ?? $detail->alternate_number ?? 'N/A',
            'house_no' => $detail->address,
            'city' => $detail->city ?? 'N/A',
            'state' => $detail->state ?? 'N/A',
            'pin_code' => $detail->pincode ?? 'N/A',
            'country' => $detail->country ?? 'India',
            'address_type' => 'Home',
            'is_default' => true,
        ]);
    }

    protected function logActivity(string $action, string $description, int $customerId): void
    {
        ActivityLog::create([
            'module_name' => 'customer',
            'record_id' => $customerId,
            'action_type' => $action,
            'old_data' => null,
            'new_data' => null,
            'description' => $description,
            'created_by_id' => Auth::id() ?? $customerId,
            'ip_address' => Request::ip(),
            'browser' => 'Unknown',
            'user_agent' => Request::header('User-Agent'),
        ]);
    }
}
