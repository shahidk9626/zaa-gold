<?php

namespace App\Services;

use App\Models\GoldBooking;
use App\Models\BookingEmiSchedule;
use App\Models\BookingPayment;
use App\Models\ActivityLog;
use App\Models\BookingStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\EmiCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentService
{
    protected $emiService;
    protected $invoiceService;
    protected $paymentGatewayService;

    public function __construct(EmiCalculationService $emiService, InvoiceService $invoiceService, PaymentGatewayService $paymentGatewayService)
    {
        $this->emiService = $emiService;
        $this->invoiceService = $invoiceService;
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Create a booking payment transaction and Cashfree session.
     */
    public function initiateBookingGatewayPayment(GoldBooking $booking, bool $isAdminSession = false): array
    {
        $customer = User::with('customerDetail')->findOrFail($booking->customer_id);
        $transaction = DB::transaction(function () use ($booking) {
            return PaymentTransaction::create([
                'transaction_number' => $this->generateTransactionNumber(),
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'payment_type' => 'booking',
                'gateway' => 'cashfree',
                'gateway_order_id' => $this->generateGatewayOrderId(),
                'payment_token' => Str::uuid()->toString(),
                'amount' => $booking->monthly_emi,
                'currency' => 'INR',
                'payment_status' => 'Pending',
                'created_by_id' => Auth::id() ?? $booking->customer_id,
                'updated_by_id' => Auth::id() ?? $booking->customer_id,
            ]);
        });

        $transaction->payment_url = route('payments.links.pay', $transaction->payment_token, true);
        $transaction->save();

        $payload = $this->buildCashfreeOrderPayload($booking, $transaction, $customer, $isAdminSession);

        try {
            $session = $this->paymentGatewayService->generatePaymentSession($transaction, $payload);
        } catch (\Throwable $e) {
            $transaction->update([
                'payment_status' => 'Failed',
                'failure_reason' => $e->getMessage(),
                'gateway_response' => ['error' => $e->getMessage()],
            ]);

            $this->logActivity('booking_payment_failed', "Cashfree session generation failed for {$transaction->transaction_number}: {$e->getMessage()}", $booking->id);

            throw $e;
        }

        $transaction->update([
            'payment_status' => 'Processing',
            'gateway_response' => $session,
        ]);

        $this->logActivity('booking_payment_initiated', "Booking payment transaction {$transaction->transaction_number} created.", $booking->id);
        $this->logActivity('redirected_to_cashfree', "Customer redirected to Cashfree for {$transaction->transaction_number}.", $booking->id);

        return [
            'transaction' => $transaction->refresh(),
            'session' => $session,
            'payment_session_id' => $session['payment_session_id'] ?? null,
            'gateway_order_id' => $transaction->gateway_order_id,
        ];
    }

    /**
     * Create an EMI payment transaction and Cashfree session.
     */
    public function initiateEmiGatewayPayment(
        BookingEmiSchedule $schedule,
        User $initiatedBy,
        string $source = 'customer_self',
        $expiresAt = null
    ): PaymentTransaction {
        $schedule->loadMissing(['booking.customer.customerDetail', 'booking.product']);
        $booking = $schedule->booking;
        $customer = $booking->customer;

        if ($schedule->status === 'Paid') {
            throw new \RuntimeException('This EMI installment is already paid.');
        }

        $transaction = DB::transaction(function () use ($schedule, $booking, $initiatedBy, $expiresAt) {
            return PaymentTransaction::create([
                'transaction_number' => $this->generateTransactionNumber(),
                'booking_id' => $booking->id,
                'emi_schedule_id' => $schedule->id,
                'customer_id' => $booking->customer_id,
                'payment_type' => 'emi',
                'gateway' => 'cashfree',
                'gateway_order_id' => $this->generateGatewayOrderId(),
                'payment_token' => Str::uuid()->toString(),
                'amount' => $this->calculateEmiPayableAmount($booking, $schedule),
                'currency' => 'INR',
                'payment_status' => 'Pending',
                'link_status' => 'Pending',
                'generated_at' => now(),
                'expires_at' => $expiresAt,
                'generated_by_id' => $initiatedBy->id,
                'created_by_id' => $initiatedBy->id,
                'updated_by_id' => $initiatedBy->id,
            ]);
        });

        $transaction->payment_url = route('payments.links.pay', $transaction->payment_token, true);
        $payload = $this->buildEmiCashfreeOrderPayload($booking, $schedule, $transaction, $customer);
        $transaction->gateway_request = $payload;
        $transaction->save();

        try {
            $session = $this->paymentGatewayService->generatePaymentSession($transaction, $payload);
        } catch (\Throwable $e) {
            $transaction->update([
                'payment_status' => 'Failed',
                'link_status' => 'Failed',
                'failure_reason' => $e->getMessage(),
                'gateway_response' => ['error' => $e->getMessage()],
            ]);

            $this->logActivity('emi_payment_failed', "Cashfree session generation failed for {$transaction->transaction_number}: {$e->getMessage()}", $booking->id);

            throw $e;
        }

        $transaction->update([
            'payment_status' => 'Processing',
            'gateway_response' => $session,
        ]);

        $this->logActivity('emi_payment_initiated', "EMI payment transaction {$transaction->transaction_number} created for installment #{$schedule->installment_number}.", $booking->id);

        if ($source === 'customer_self') {
            $this->logActivity('customer_redirected_to_cashfree', "Customer redirected to Cashfree for EMI transaction {$transaction->transaction_number}.", $booking->id);
        } else {
            $this->logActivity('payment_link_generated', "Payment link generated for EMI transaction {$transaction->transaction_number}.", $booking->id);
        }

        return $transaction->refresh();
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
    public function processFirstEmiPayment(GoldBooking $booking, array $paymentOverrides = [])
    {
        $firstEmi = BookingEmiSchedule::where('booking_id', $booking->id)
            ->where('installment_number', 1)
            ->firstOrFail();

        $paymentData = array_merge([
            'payment_mode' => 'Cash',
            'transaction_reference' => 'AUTO-INIT',
            'remarks' => 'First EMI collected automatically on booking confirmation.',
            'payment_date' => now(),
        ], $paymentOverrides);

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

    public function generateTransactionNumber(): string
    {
        $year = now()->format('y');
        $prefix = "TXN{$year}";

        $latest = PaymentTransaction::where('transaction_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latest) {
            return $prefix . '0000001';
        }

        $lastNumber = substr($latest->transaction_number, 5);

        return $prefix . str_pad((int) $lastNumber + 1, 7, '0', STR_PAD_LEFT);
    }

    public function generateGatewayOrderId(): string
    {
        do {
            $orderId = 'CFBOOK' . now()->format('ymdHis') . strtoupper(Str::random(6));
        } while (PaymentTransaction::where('gateway_order_id', $orderId)->exists());

        return $orderId;
    }

    public function calculateEmiPayableAmount(GoldBooking $booking, BookingEmiSchedule $schedule): float
    {
        $lateFee = 0.00;

        if (now()->startOfDay()->gt(Carbon::parse($schedule->due_date)->startOfDay())) {
            $lateFee = (float) $this->emiService->calculateLateFee($booking->emiPlan, $schedule->emi_amount);
        }

        return round((float) $schedule->emi_amount + $lateFee, 2);
    }

    protected function buildCashfreeOrderPayload(GoldBooking $booking, PaymentTransaction $transaction, User $customer, bool $isAdminSession = false): array
    {
        $mobile = $customer->customerDetail->phone_number
            ?? $customer->phone
            ?? $customer->whatsapp_number
            ?? '9999999999';

        $returnUrl = $isAdminSession
            ? route('admin.booking-payments.callback', ['transaction' => $transaction->id], true)
            : route('customer.booking-payments.callback', ['transaction' => $transaction->id], true);

        return [
            'order_id' => $transaction->gateway_order_id,
            'order_amount' => (float) $transaction->amount,
            'order_currency' => $transaction->currency,
            'order_note' => "Booking payment for {$booking->booking_number}",
            'customer_details' => [
                'customer_id' => (string) $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => preg_replace('/\D+/', '', $mobile) ?: '9999999999',
            ],
            'order_meta' => [
                'return_url' => $returnUrl . '?order_id={order_id}',
                'notify_url' => route('payments.cashfree.webhook', [], true),
            ],
            'order_tags' => [
                'payment_type' => 'booking',
                'booking_id' => (string) $booking->id,
                'transaction_number' => $transaction->transaction_number,
            ],
        ];
    }

    protected function buildEmiCashfreeOrderPayload(GoldBooking $booking, BookingEmiSchedule $schedule, PaymentTransaction $transaction, User $customer): array
    {
        $mobile = $customer->customerDetail->phone_number
            ?? $customer->phone
            ?? $customer->whatsapp_number
            ?? '9999999999';

        return [
            'order_id' => $transaction->gateway_order_id,
            'order_amount' => (float) $transaction->amount,
            'order_currency' => $transaction->currency,
            'order_note' => "EMI #{$schedule->installment_number} payment for {$booking->booking_number}",
            'customer_details' => [
                'customer_id' => (string) $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => preg_replace('/\D+/', '', $mobile) ?: '9999999999',
            ],
            'order_meta' => [
                'return_url' => route('payments.gateway.callback', ['transaction' => $transaction->id], true) . '?order_id={order_id}',
                'notify_url' => route('payments.cashfree.webhook', [], true),
            ],
            'order_tags' => [
                'payment_type' => 'emi',
                'booking_id' => (string) $booking->id,
                'emi_schedule_id' => (string) $schedule->id,
                'installment_number' => (string) $schedule->installment_number,
                'transaction_number' => $transaction->transaction_number,
            ],
        ];
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
