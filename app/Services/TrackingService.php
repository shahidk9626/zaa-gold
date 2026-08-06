<?php

namespace App\Services;

use App\Models\BookingDelivery;

class TrackingService
{
    public function timelineSteps(BookingDelivery $delivery): array
    {
        if ($delivery->delivery_method === 'Branch Pickup') {
            return ['Delivery Requested', 'Pickup Approved', 'Ready for Pickup', 'Collected'];
        }

        return [
            'Delivery Requested',
            'Approved',
            'Packed',
            'Ready for Dispatch',
            'Dispatched',
            'In Transit',
            'Out for Delivery',
            'Delivered',
        ];
    }

    public function customerStatus(BookingDelivery $delivery): string
    {
        return match ($delivery->delivery_status) {
            'Pending Admin Approval' => 'Delivery Requested',
            'Approved' => $delivery->delivery_method === 'Branch Pickup' ? 'Pickup Approved' : 'Approved',
            'Ready For Dispatch' => $delivery->delivery_method === 'Branch Pickup' ? 'Ready for Pickup' : 'Ready for Dispatch',
            'Collected' => 'Collected',
            default => $delivery->delivery_status,
        };
    }
}
