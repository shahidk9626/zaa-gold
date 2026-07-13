<?php

namespace App\Services;

use App\Models\GoldBooking;
use App\Models\BookingEmiSchedule;
use App\Models\BookingDelivery;
use App\Models\ActivityLog;
use App\Events\DeliveryRequestedEvent;
use App\Events\DeliveryApprovedEvent;
use App\Events\DeliveryDispatchedEvent;
use App\Events\DeliveryDeliveredEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class DeliveryService
{
    /**
     * Request delivery for completed bookings with zero outstanding balance
     */
    public function requestDelivery(GoldBooking $booking, array $data)
    {
        // 1. Validate Booking Status = Completed
        if ($booking->status !== 'Completed') {
            throw new \Exception("Gold delivery can only be requested for Completed bookings. Current status: {$booking->status}.");
        }

        // 2. Validate Outstanding Balance = 0 (No unpaid EMI schedules)
        $hasUnpaid = BookingEmiSchedule::where('booking_id', $booking->id)
            ->where('status', '!=', 'Paid')
            ->exists();
        if ($hasUnpaid) {
            throw new \Exception("Delivery request is blocked because there is a remaining outstanding balance on this booking plan.");
        }

        // 3. Prevent duplicate active delivery requests
        $activeRequest = BookingDelivery::where('booking_id', $booking->id)
            ->whereNotIn('delivery_status', ['Cancelled', 'Returned'])
            ->exists();
        if ($activeRequest) {
            throw new \Exception("An active delivery request already exists for this booking.");
        }

        return DB::transaction(function () use ($booking, $data) {
            $deliveryNumber = $this->generateDeliveryNumber();

            $delivery = new BookingDelivery();
            $delivery->delivery_number = $deliveryNumber;
            $delivery->booking_id = $booking->id;
            $delivery->customer_id = $booking->customer_id;
            $delivery->delivery_method = $data['delivery_method']; // Office Pickup, Courier, Branch Pickup
            $delivery->delivery_status = 'Requested';
            $delivery->request_date = now();
            
            if ($data['delivery_method'] === 'Courier') {
                $delivery->delivery_address = $data['delivery_address'] ?? $booking->customer->customerDetail->address ?? 'N/A';
            } elseif ($data['delivery_method'] === 'Branch Pickup') {
                $delivery->pickup_branch = $data['pickup_branch'] ?? 'Main Branch';
                $delivery->pickup_date = $data['pickup_date'] ?? null;
                $delivery->pickup_time = $data['pickup_time'] ?? null;
            }

            $delivery->remarks = $data['remarks'] ?? null;
            $delivery->created_by_id = Auth::id() ?? 1;
            $delivery->save();

            // Log activity
            $this->logActivityDirect('delivery_requested', "Gold delivery requested for Booking {$booking->booking_number} (Delivery: {$deliveryNumber})", $booking->id);

            // Trigger reusable placeholder event
            event(new DeliveryRequestedEvent($delivery));

            return $delivery;
        });
    }

    /**
     * Approve Delivery request (Generate OTP & Challan PDF)
     */
    public function approveDelivery(BookingDelivery $delivery, array $data = [])
    {
        if ($delivery->delivery_status !== 'Requested') {
            throw new \Exception("Only Requested deliveries can be approved. Current status: {$delivery->delivery_status}.");
        }

        return DB::transaction(function () use ($delivery, $data) {
            $delivery->delivery_status = 'Approved';
            $delivery->approved_date = now();
            
            // Generate OTP for Office/Branch pickup
            if (in_array($delivery->delivery_method, ['Office Pickup', 'Branch Pickup'])) {
                $delivery->otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiryHours = config('app.delivery_otp_expiry_hours', 24);
                $delivery->otp_expires_at = now()->addHours($expiryHours);
                
                $this->logActivityDirect('otp_generated', "OTP generated for Delivery {$delivery->delivery_number}", $delivery->booking_id);
            }

            $delivery->remarks = $data['remarks'] ?? $delivery->remarks;
            $delivery->updated_by_id = Auth::id() ?? 1;
            $delivery->save();

            // Generate delivery challan PDF
            $pdfPath = $this->generateDeliveryChallanPdf($delivery);
            $delivery->pdf_path = $pdfPath;
            $delivery->save();

            // Log activity
            $this->logActivityDirect('delivery_approved', "Delivery {$delivery->delivery_number} approved.", $delivery->booking_id);

            // Trigger reusable placeholder event
            event(new DeliveryApprovedEvent($delivery));

            return $delivery;
        });
    }

    /**
     * Dispatch Delivery (Courier tracking assignment)
     */
    public function dispatchDelivery(BookingDelivery $delivery, array $data)
    {
        if (!in_array($delivery->delivery_status, ['Approved', 'Ready For Dispatch'])) {
            throw new \Exception("Delivery must be Approved or Ready For Dispatch before dispatching.");
        }

        return DB::transaction(function () use ($delivery, $data) {
            $delivery->delivery_status = 'Dispatched';
            $delivery->dispatch_date = now();
            
            if ($delivery->delivery_method === 'Courier') {
                $delivery->courier_partner = $data['courier_partner'] ?? null;
                $delivery->tracking_number = $data['tracking_number'] ?? null;
                $delivery->tracking_url = $data['tracking_url'] ?? null;
            }

            $delivery->remarks = $data['remarks'] ?? $delivery->remarks;
            $delivery->updated_by_id = Auth::id() ?? 1;
            $delivery->save();

            // Regenerate PDF with updated tracking details
            $pdfPath = $this->generateDeliveryChallanPdf($delivery);
            $delivery->pdf_path = $pdfPath;
            $delivery->save();

            $this->logActivityDirect('delivery_dispatched', "Delivery {$delivery->delivery_number} marked as Dispatched.", $delivery->booking_id);

            // Trigger event
            event(new DeliveryDispatchedEvent($delivery));

            return $delivery;
        });
    }

    /**
     * Complete Delivery (Verify OTP if Pickup)
     */
    public function completeDelivery(BookingDelivery $delivery, array $data = [])
    {
        if (!in_array($delivery->delivery_status, ['Approved', 'Dispatched', 'Out For Delivery'])) {
            throw new \Exception("Delivery cannot be completed from current status: {$delivery->delivery_status}.");
        }

        return DB::transaction(function () use ($delivery, $data) {
            // Verify OTP for Pickup methods
            if (in_array($delivery->delivery_method, ['Office Pickup', 'Branch Pickup'])) {
                if (!isset($data['otp'])) {
                    throw new \Exception("OTP code is required to complete pickup verification.");
                }

                if ($delivery->otp_verified_at) {
                    // Already verified
                } else {
                    if (now()->gt($delivery->otp_expires_at)) {
                        throw new \Exception("The OTP code has expired. Please regenerate OTP.");
                    }

                    if ($delivery->otp !== $data['otp']) {
                        throw new \Exception("Invalid OTP code supplied.");
                    }

                    $delivery->otp_verified_at = now();
                    $this->logActivityDirect('otp_verified', "OTP verified successfully for Delivery {$delivery->delivery_number}", $delivery->booking_id);
                }
            }

            // Capture Receiver Details
            $delivery->receiver_name = $data['receiver_name'] ?? $delivery->customer->name;
            $delivery->receiver_mobile = $data['receiver_mobile'] ?? $delivery->customer->phone;
            $delivery->receiver_id_proof = $data['receiver_id_proof'] ?? null;

            $delivery->delivery_status = 'Delivered';
            $delivery->delivered_date = now();
            $delivery->remarks = $data['remarks'] ?? $delivery->remarks;
            $delivery->updated_by_id = Auth::id() ?? 1;
            $delivery->save();

            // Regenerate PDF with receiver signature information
            $pdfPath = $this->generateDeliveryChallanPdf($delivery);
            $delivery->pdf_path = $pdfPath;
            $delivery->save();

            $this->logActivityDirect('delivery_completed', "Gold Delivery {$delivery->delivery_number} completed and delivered successfully.", $delivery->booking_id);

            // Trigger event
            event(new DeliveryDeliveredEvent($delivery));

            return $delivery;
        });
    }

    /**
     * Cancel delivery request
     */
    public function cancelDelivery(BookingDelivery $delivery, $remarks)
    {
        if ($delivery->delivery_status === 'Delivered') {
            throw new \Exception("Cannot cancel a completed delivery.");
        }

        return DB::transaction(function () use ($delivery, $remarks) {
            $delivery->delivery_status = 'Cancelled';
            $delivery->remarks = $remarks;
            $delivery->updated_by_id = Auth::id() ?? 1;
            $delivery->save();

            $this->logActivityDirect('delivery_cancelled', "Delivery request {$delivery->delivery_number} cancelled. Reason: {$remarks}", $delivery->booking_id);

            return $delivery;
        });
    }

    /**
     * Regenerate OTP
     */
    public function regenerateOtp(BookingDelivery $delivery)
    {
        if (!in_array($delivery->delivery_method, ['Office Pickup', 'Branch Pickup'])) {
            throw new \Exception("OTP is not supported for delivery method: {$delivery->delivery_method}.");
        }

        return DB::transaction(function () use ($delivery) {
            $delivery->otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $delivery->otp_expires_at = now()->addHours(config('app.delivery_otp_expiry_hours', 24));
            $delivery->otp_verified_at = null;
            $delivery->save();

            $this->logActivityDirect('otp_generated', "OTP regenerated for Delivery {$delivery->delivery_number}", $delivery->booking_id);

            return $delivery;
        });
    }

    /**
     * Generate unique sequential delivery numbers (e.g. DEL260000001)
     */
    public function generateDeliveryNumber()
    {
        $year = now()->format('y'); // e.g. "26" for 2026
        $prefix = "DEL" . $year;

        $latest = BookingDelivery::where('delivery_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latest) {
            return $prefix . "0000001";
        }

        $lastNumber = substr($latest->delivery_number, 5);
        $nextNumber = str_pad((int)$lastNumber + 1, 7, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    /**
     * Compile PDF Challan for delivery and save to storage
     */
    public function generateDeliveryChallanPdf(BookingDelivery $delivery)
    {
        $booking = $delivery->booking;
        $customer = $delivery->customer;
        $product = $booking->product;
        
        // Find GST Invoice related to booking/payments if it exists
        $invoice = \App\Models\GstInvoice::where('booking_id', $booking->id)->first();

        // Convert QR image of the booking certificate to base64
        $qrBase64 = '';
        if ($booking->certificate && $booking->certificate->qr_code && Storage::disk('public')->exists($booking->certificate->qr_code)) {
            $qrContent = Storage::disk('public')->get($booking->certificate->qr_code);
            if (!empty($qrContent)) {
                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrContent);
            }
        }

        $pdfData = [
            'delivery' => $delivery,
            'booking' => $booking,
            'customer' => $customer,
            'product' => $product,
            'invoice' => $invoice,
            'qrImageSrc' => $qrBase64,
            'generatedAt' => now()->format('d M Y, h:i A'),
            'generatedBy' => auth()->user()->name ?? 'System'
        ];

        $pdf = Pdf::loadView('admin.deliveries.challan', $pdfData);
        $pdfPath = 'deliveries/CHALLAN_' . $delivery->delivery_number . '.pdf';

        Storage::disk('public')->put($pdfPath, $pdf->output());

        return $pdfPath;
    }

    /**
     * Direct logging inside ActivityLog schema
     */
    protected function logActivityDirect($action, $description, $recordId)
    {
        $userAgent = Request::header('User-Agent');
        $browser = 'Unknown';
        if (!empty($userAgent)) {
            if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) $browser = 'Internet Explorer';
            elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
            elseif (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
            elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
            elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) $browser = 'Opera';
            elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
        }

        ActivityLog::create([
            'module_name' => 'gold_booking',
            'record_id' => $recordId,
            'action_type' => $action,
            'old_data' => null,
            'new_data' => null,
            'description' => $description,
            'created_by_id' => Auth::id() ?? 1,
            'ip_address' => Request::ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
