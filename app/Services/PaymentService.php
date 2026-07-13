<?php

namespace App\Services;

use App\Models\GoldBooking;
use App\Models\BookingEmiSchedule;
use App\Models\BookingPayment;
use App\Models\ActivityLog;
use App\Models\BookingStatusHistory;
use App\Services\EmiCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class PaymentService
{
    protected $emiService;
    protected $invoiceService;

    public function __construct(EmiCalculationService $emiService, InvoiceService $invoiceService)
    {
        $this->emiService = $emiService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Generate complete EMI Schedule for booking
     */
    public function generateScheduleForBooking(GoldBooking $booking)
    {
        return DB::transaction(function () use ($booking) {
            $scheduleData = $this->emiService->generateSchedule($booking->emiPlan, $booking->locked_gold_value, $booking->booking_date);

            foreach ($scheduleData as $row) {
                BookingEmiSchedule::create([
                    'booking_id' => $booking->id,
                    'installment_number' => $row['installment_number'],
                    'due_date' => $row['due_date'],
                    'opening_principal' => $row['opening_principal'],
                    'principal_amount' => $row['principal_amount'],
                    'interest_amount' => $row['interest_amount'],
                    'emi_amount' => $row['emi_amount'],
                    'closing_principal' => $row['closing_principal'],
                    'outstanding_balance' => $row['outstanding_balance'],
                    'status' => 'Pending',
                    'created_by_id' => Auth::id() ?? $booking->created_by_id,
                    'updated_by_id' => Auth::id() ?? $booking->updated_by_id,
                ]);
            }

            $this->logActivity('emi_schedule_generated', "EMI Schedule Generated for Booking {$booking->booking_number}", $booking->id);
        });
    }

    /**
     * Automatically process payment for EMI #1
     */
    public function processFirstEmiPayment(GoldBooking $booking)
    {
        $firstEmi = BookingEmiSchedule::where('booking_id', $booking->id)
            ->where('installment_number', 1)
            ->firstOrFail();

        $paymentData = [
            'payment_mode' => 'Cash',
            'transaction_reference' => 'AUTO-INIT',
            'remarks' => 'First EMI collected automatically on booking confirmation.',
            'payment_date' => now(),
        ];

        return $this->collectPayment($booking, $firstEmi, $paymentData, true);
    }

    /**
     * Collect payment for an EMI installment (or partial payment)
     */
    public function collectPayment(GoldBooking $booking, BookingEmiSchedule $schedule, array $data, $isFirstEmi = false)
    {
        return DB::transaction(function () use ($booking, $schedule, $data, $isFirstEmi) {
            $paymentDate = isset($data['payment_date']) ? Carbon::parse($data['payment_date']) : now();
            
            // Calculate Late Fee if payment is past the due date (excluding first EMI)
            $lateFee = 0.00;
            if (!$isFirstEmi && $paymentDate->startOfDay()->gt(Carbon::parse($schedule->due_date)->startOfDay())) {
                $lateFee = (float)$this->emiService->calculateLateFee($booking->emiPlan, $schedule->emi_amount);
                
                if ($lateFee > 0) {
                    $this->logActivity('late_fee_applied', "Late fee of ₹" . number_format($lateFee, 2) . " applied to EMI #{$schedule->installment_number} for Booking {$booking->booking_number}", $booking->id);
                }
            }

            // In our system, the GST and charges are already calculated into the EMI amount.
            // Let's compute the proportional monthly GST amount for recording.
            $monthlyGst = round(($booking->gst_on_gold_amount + $booking->gst_on_charges_amount) / $booking->duration_months, 2);

            $paymentNumber = $this->generatePaymentNumber();
            $receiptNumber = $this->generateReceiptNumber();

            // Create Booking Payment
            $payment = BookingPayment::create([
                'payment_number' => $paymentNumber,
                'receipt_number' => $receiptNumber,
                'booking_id' => $booking->id,
                'emi_schedule_id' => $schedule->id,
                'customer_id' => $booking->customer_id,
                'payment_mode' => $data['payment_mode'] ?? 'Cash',
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'amount_paid' => $schedule->emi_amount + $lateFee,
                'principal_paid' => $schedule->principal_amount,
                'interest_paid' => $schedule->interest_amount,
                'late_fee_paid' => $lateFee,
                'gst_paid' => $monthlyGst,
                'payment_date' => $paymentDate,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'Paid',
                'created_by_id' => Auth::id() ?? $booking->created_by_id,
                'updated_by_id' => Auth::id() ?? $booking->updated_by_id,
            ]);

            // Update EMI Schedule record
            $schedule->status = 'Paid';
            $schedule->paid_at = $paymentDate;
            $schedule->payment_id = $payment->id;
            $schedule->late_fee = $lateFee;
            $schedule->save();

            // Recalculate and update outstanding details
            $this->logActivity('outstanding_updated', "Outstanding updated for Booking {$booking->booking_number}. Remaining Balance: ₹" . number_format($schedule->outstanding_balance, 2), $booking->id);

            // Log activities
            $actionType = $isFirstEmi ? 'first_emi_paid' : 'payment_collected';
            $description = $isFirstEmi 
                ? "First EMI of ₹" . number_format($payment->amount_paid, 2) . " paid automatically for Booking {$booking->booking_number}"
                : "EMI Payment #{$schedule->installment_number} of ₹" . number_format($payment->amount_paid, 2) . " collected (Receipt: {$receiptNumber})";
            
            $this->logActivity($actionType, $description, $booking->id);
            $this->logActivity('receipt_generated', "Receipt {$receiptNumber} generated for Payment {$paymentNumber}", $booking->id);

            // Update Booking Status to ACTIVE if not already active
            if ($booking->status !== 'Active') {
                $booking->status = 'Active';
                $booking->status_change_remarks = 'Activated automatically after first EMI payment.';
                $booking->save();
                
                $this->logActivity('booking_activated', "Booking {$booking->booking_number} activated", $booking->id);
            }

            // Automatically generate GST Invoice after successful EMI payment
            $this->invoiceService->generateInvoice($payment);

            return $payment;
        });
    }

    /**
     * Generate sequential unique payment numbers (e.g. PAY260000001)
     */
    public function generatePaymentNumber()
    {
        $year = now()->format('y'); // e.g. "26" for 2026
        $prefix = "PAY" . $year;

        $latest = BookingPayment::where('payment_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latest) {
            return $prefix . "0000001";
        }

        $lastNumber = substr($latest->payment_number, 5);
        $nextNumber = str_pad((int)$lastNumber + 1, 7, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    /**
     * Generate sequential unique receipt numbers (e.g. RCP260000001)
     */
    public function generateReceiptNumber()
    {
        $year = now()->format('y'); // e.g. "26" for 2026
        $prefix = "RCP" . $year;

        $latest = BookingPayment::where('receipt_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latest) {
            return $prefix . "0000001";
        }

        $lastNumber = substr($latest->receipt_number, 5);
        $nextNumber = str_pad((int)$lastNumber + 1, 7, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    /**
     * Write logs inside the ActivityLog schema
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
