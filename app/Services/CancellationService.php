<?php

namespace App\Services;

use App\Models\GoldBooking;
use App\Models\CancellationRequest;
use App\Models\ActivityLog;
use App\Events\CancellationRequested;
use App\Events\CancellationApproved;
use App\Events\CancellationRejected;
use App\Events\CustomerRetained;
use App\Events\RefundInitiated;
use App\Events\RefundCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class CancellationService
{
    protected $calculationService;

    public function __construct(RefundCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Submit a new cancellation request for a booking.
     */
    public function createRequest(GoldBooking $booking, string $reason, array $bankDetails = []): CancellationRequest
    {
        // Prevent duplicate/overlapping active requests
        $hasActiveRequest = CancellationRequest::where('booking_id', $booking->id)
            ->whereIn('status', ['Requested', 'Under Review', 'Approved', 'Refund Initiated', 'Refund Completed'])
            ->exists();

        if ($hasActiveRequest) {
            throw new \RuntimeException('An active or completed cancellation request already exists for this booking.');
        }

        // Prevent cancellations on completed deliveries or already cancelled plans
        if ($booking->status === 'Cancelled') {
            throw new \RuntimeException('This booking is already cancelled.');
        }

        if ($booking->deliveries()->where('delivery_status', 'Delivered')->exists()) {
            throw new \RuntimeException('Gold has already been delivered for this booking. Cancellation is not possible.');
        }

        return DB::transaction(function () use ($booking, $reason, $bankDetails) {
            $refundDetails = $this->calculationService->calculateRefund($booking);
            $requestNumber = $this->generateRequestNumber();

            $request = CancellationRequest::create([
                'request_number' => $requestNumber,
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'cancellation_reason' => mb_substr($reason, 0, 500),
                'cancellation_charge_percent' => $refundDetails['cancellation_charge_percent'],
                'cancellation_charge_amount' => $refundDetails['cancellation_charge_amount'],
                'total_amount_paid' => $refundDetails['total_amount_paid'],
                'refund_amount' => $refundDetails['refund_amount'],
                'status' => 'Requested',
                'bank_name' => $bankDetails['bank_name'] ?? null,
                'bank_account_number' => $bankDetails['bank_account_number'] ?? null,
                'bank_ifsc' => $bankDetails['bank_ifsc'] ?? null,
                'created_by_id' => Auth::id() ?? $booking->customer_id,
                'updated_by_id' => Auth::id() ?? $booking->customer_id,
            ]);

            // Log activity
            $this->logActivity(
                'cancellation_requested',
                "Customer requested cancellation for Booking {$booking->booking_number}. Estimated refund: ₹" . number_format($refundDetails['refund_amount'], 2),
                $booking->id
            );

            // Dispatch event
            event(new CancellationRequested($request));

            return $request;
        });
    }

    /**
     * Admin action to update cancellation request status
     */
    public function updateStatus(CancellationRequest $request, string $newStatus, string $remark, array $additionalData = []): CancellationRequest
    {
        $oldStatus = $request->status;

        // Mandatory remark check
        if (empty(trim($remark))) {
            throw new \InvalidArgumentException('Mandatory remark is required for any cancellation action.');
        }

        // Irreversible status check
        if (in_array($oldStatus, ['Approved', 'Rejected', 'Refund Completed'])) {
            throw new \RuntimeException("This cancellation request is already in a final state ({$oldStatus}) and cannot be modified.");
        }

        return DB::transaction(function () use ($request, $newStatus, $remark, $additionalData) {
            $booking = $request->booking;
            $currentUserId = Auth::id() ?? 1;

            $request->status = $newStatus;
            $request->admin_remark = $remark;
            $request->updated_by_id = $currentUserId;

            if ($newStatus === 'Approved') {
                $request->approved_by_id = $currentUserId;
                $request->approved_at = now();

                // Direct parent Booking update
                $booking->status = 'Cancelled';
                $booking->status_change_remarks = "Cancellation approved via Request #{$request->request_number}. Remark: {$remark}";
                $booking->save();

                $this->logActivity('cancellation_approved', "Cancellation Request #{$request->request_number} Approved. Booking {$booking->booking_number} Cancelled.", $booking->id);
                event(new CancellationApproved($request));

            } elseif ($newStatus === 'Rejected') {
                $this->logActivity('cancellation_rejected', "Cancellation Request #{$request->request_number} Rejected. Booking {$booking->booking_number} remains active.", $booking->id);
                event(new CancellationRejected($request));

            } elseif ($newStatus === 'Customer Retained') {
                $this->logActivity('customer_retained', "Customer retained for Booking {$booking->booking_number}. Cancellation Request marked as Retained.", $booking->id);
                event(new CustomerRetained($request));

            } elseif ($newStatus === 'Refund Initiated') {
                $request->refund_initiated_at = now();

                // Direct parent Booking update
                $booking->status = 'Refund Initiated';
                $booking->status_change_remarks = "Refund initiated via Request #{$request->request_number}. Remark: {$remark}";
                $booking->save();

                $this->logActivity('refund_initiated', "Refund initiated for Cancellation Request #{$request->request_number}.", $booking->id);
                event(new RefundInitiated($request));

            } elseif ($newStatus === 'Refund Completed') {
                $request->refund_completed_at = now();
                $request->refund_transaction_number = $additionalData['refund_transaction_number'] ?? null;
                $request->refund_date = $additionalData['refund_date'] ?? now();
                $request->refund_mode = $additionalData['refund_mode'] ?? 'Online';

                // Direct parent Booking update
                $booking->status = 'Refunded';
                $booking->status_change_remarks = "Refund completed via Request #{$request->request_number}. Txn: {$request->refund_transaction_number}. Remark: {$remark}";
                $booking->save();

                $this->logActivity(
                    'refund_completed',
                    "Refund completed for Cancellation Request #{$request->request_number}. Txn: {$request->refund_transaction_number}.",
                    $booking->id
                );
                event(new RefundCompleted($request));
            } else {
                // E.g. Under Review or Request More Information
                $this->logActivity('cancellation_review_updated', "Cancellation Request #{$request->request_number} status updated to: {$newStatus}.", $booking->id);
            }

            $request->save();

            return $request;
        });
    }

    /**
     * Generate sequential cancellation request numbers (e.g. CAN260000001)
     */
    protected function generateRequestNumber(): string
    {
        $year = now()->format('y');
        $prefix = "CAN" . $year;

        $latest = CancellationRequest::where('request_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latest) {
            return $prefix . "0000001";
        }

        $lastNumber = substr($latest->request_number, 5);
        $nextNumber = str_pad((int)$lastNumber + 1, 7, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    /**
     * Log cancellation activity in audit database
     */
    protected function logActivity($action, $description, $recordId)
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
            'description' => $description,
            'created_by_id' => Auth::id() ?? 1,
            'ip_address' => Request::ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }
}
