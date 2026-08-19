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
    protected $financialService;

    public function __construct(
        EmiCalculationService $emiService,
        InvoiceService $invoiceService,
        PaymentGatewayService $paymentGatewayService,
        FinancialCalculationService $financialService
    )
    {
        $this->emiService = $emiService;
        $this->invoiceService = $invoiceService;
        $this->paymentGatewayService = $paymentGatewayService;
        $this->financialService = $financialService;
    }

    /**
     * Create a booking payment transaction and Cashfree session.
     */
    public function initiateBookingGatewayPayment(GoldBooking $booking, bool $isAdminSession = false): array
    {
        if (in_array($booking->status, ['Cancelled', 'Refund Initiated', 'Refunded'])) {
            throw new \RuntimeException('This plan has been cancelled and no further EMI payments can be made.');
        }
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

        if (in_array($booking->status, ['Cancelled', 'Refund Initiated', 'Refunded'])) {
            throw new \RuntimeException('This plan has been cancelled and no further EMI payments can be made.');
        }

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
            $offer = $booking->offer_id ? \App\Models\Offer::find($booking->offer_id) : null;
            $scheduleData = $this->emiService->generateSchedule($booking->emiPlan, $booking->locked_gold_value, $booking->booking_date, $offer);

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
                    'status' => $row['status'] ?? 'Pending',
                    'waived_reason' => ($row['status'] ?? 'Pending') === 'Waived' ? 'Offer Benefit' : null,
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
        if (in_array($booking->status, ['Cancelled', 'Refund Initiated', 'Refunded'])) {
            throw new \RuntimeException('This plan has been cancelled and no further EMI payments can be made.');
        }
        return DB::transaction(function () use ($booking, $schedule, $data, $isFirstEmi) {
            $paymentDate = isset($data['payment_date'])
                ? (is_string($data['payment_date']) ? Carbon::parse($data['payment_date']) : clone $data['payment_date'])
                : now();

            if ($paymentDate->format('H:i:s') === '00:00:00') {
                $paymentDate->setTimeFrom(now());
            }
            
            // Calculate Late Fee if payment is past the due date (excluding first EMI)
            $lateFee = 0.00;
            if (!$isFirstEmi && (clone $paymentDate)->startOfDay()->gt(Carbon::parse($schedule->due_date)->startOfDay())) {
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
            $basePaymentAmount = $this->financialService->payableBaseAmount($booking, $schedule);
            $totalPaymentAmount = $this->financialService->roundMoney($basePaymentAmount + $lateFee);
            $principalPaid = min((float) $schedule->principal_amount, $basePaymentAmount);
            $interestPaid = $this->financialService->roundMoney(max(0.00, $basePaymentAmount - $principalPaid));

            $payment = BookingPayment::create([
                'payment_number' => $paymentNumber,
                'receipt_number' => $receiptNumber,
                'booking_id' => $booking->id,
                'emi_schedule_id' => $schedule->id,
                'customer_id' => $booking->customer_id,
                'payment_mode' => $data['payment_mode'] ?? 'Cash',
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'amount_paid' => $totalPaymentAmount,
                'principal_paid' => $principalPaid,
                'interest_paid' => $interestPaid,
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

            $booking->refresh();
            $outstanding = $this->financialService->outstanding($booking);
            $this->logActivity('outstanding_updated', "Outstanding updated for Booking {$booking->booking_number}. Remaining Balance: ₹" . number_format($outstanding, 2), $booking->id);

            // Log activities
            $actionType = $isFirstEmi ? 'first_emi_paid' : 'payment_collected';
            $description = $isFirstEmi 
                ? "First EMI of ₹" . number_format($payment->amount_paid, 2) . " paid automatically for Booking {$booking->booking_number}"
                : "EMI Payment #{$schedule->installment_number} of ₹" . number_format($payment->amount_paid, 2) . " collected (Receipt: {$receiptNumber})";
            
            $this->logActivity($actionType, $description, $booking->id);
            $this->logActivity('receipt_generated', "Receipt {$receiptNumber} generated for Payment {$paymentNumber}", $booking->id);

            // Update Booking Status to ACTIVE if not already active
            if (!in_array($booking->status, ['Active', 'Completed'], true)) {
                $booking->status = 'Active';
                $booking->status_change_remarks = 'Activated automatically after first EMI payment.';
                $booking->save();
                
                $this->logActivity('booking_activated', "Booking {$booking->booking_number} activated", $booking->id);
            }

            if ($this->financialService->completeIfEligible($booking)) {
                $this->logActivity('booking_completed', "Booking {$booking->booking_number} completed automatically after normalized outstanding reached ₹0.00.", $booking->id);
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

        return $this->financialService->roundMoney($this->financialService->payableBaseAmount($booking, $schedule) + $lateFee);
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

    /**
     * Process cash payment flow for a booking
     */
    public function processCashBookingPayment(GoldBooking $booking, array $data)
    {
        if (in_array($booking->status, ['Cancelled', 'Refund Initiated', 'Refunded'])) {
            throw new \RuntimeException('This plan has been cancelled and no further EMI payments can be made.');
        }
        return DB::transaction(function () use ($booking, $data) {
            $bookingService = app(BookingService::class);

            // 1. Convert booking number from DRAFT to real ZG26xxxx
            if (str_starts_with((string) $booking->booking_number, 'DRAFT-')) {
                $booking->booking_number = $bookingService->generateBookingNumber();
            }
            $booking->status = 'Booked';
            $booking->booking_date = now();
            $booking->status_change_remarks = 'Booking created with Cash Collection pending verification.';
            $booking->save();

            // 2. Generate Certificate
            if (!$booking->certificate()->exists()) {
                $bookingService->generateCertificate($booking);
            }

            // 3. Generate EMI Schedule
            if (!BookingEmiSchedule::where('booking_id', $booking->id)->exists()) {
                $this->generateScheduleForBooking($booking);
            }

            $firstEmi = BookingEmiSchedule::where('booking_id', $booking->id)
                ->where('installment_number', 1)
                ->firstOrFail();

            // 4. Create Payment Transaction
            $transaction = PaymentTransaction::create([
                'transaction_number' => $this->generateTransactionNumber(),
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'payment_type' => 'booking',
                'gateway' => 'cash',
                'gateway_order_id' => 'CSHBOOK' . now()->format('ymdHis') . strtoupper(Str::random(6)),
                'amount' => $booking->monthly_emi,
                'currency' => 'INR',
                'payment_status' => 'Pending Verification',
                'created_by_id' => Auth::id() ?? $booking->customer_id,
                'updated_by_id' => Auth::id() ?? $booking->customer_id,
            ]);

            // 5. Calculate proportional monthly GST
            $monthlyGst = round(($booking->gst_on_gold_amount + $booking->gst_on_charges_amount) / $booking->duration_months, 2);

            $paymentNumber = $this->generatePaymentNumber();
            $receiptNumber = $this->generateReceiptNumber();

            // 6. Create Booking Payment (Receipt) - status is Pending Verification
            $receipt = BookingPayment::create([
                'payment_number' => $paymentNumber,
                'receipt_number' => $receiptNumber,
                'booking_id' => $booking->id,
                'emi_schedule_id' => $firstEmi->id,
                'customer_id' => $booking->customer_id,
                'payment_mode' => 'Cash',
                'transaction_reference' => $transaction->transaction_number,
                'amount_paid' => $firstEmi->emi_amount,
                'principal_paid' => $firstEmi->principal_amount,
                'interest_paid' => $firstEmi->interest_amount,
                'gst_paid' => $monthlyGst,
                'payment_date' => now(),
                'remarks' => $data['remarks'] ?? 'Cash Collection downpayment.',
                'status' => 'Pending Verification',
                'created_by_id' => Auth::id() ?? $booking->created_by_id,
                'updated_by_id' => Auth::id() ?? $booking->updated_by_id,
            ]);

            // 7. Link first EMI to this receipt
            $firstEmi->payment_id = $receipt->id;
            $firstEmi->save();

            // 8. Create Cash Collection Request
            $ccr = \App\Models\CashCollectionRequest::create([
                'collection_number' => $this->generateCollectionNumber(),
                'transaction_id' => $transaction->id,
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'payment_id' => $receipt->id,
                'collected_by_id' => Auth::id() ?? 1,
                'amount' => $booking->monthly_emi,
                'status' => 'Pending Verification',
                'collection_date' => now(),
                'remarks' => $data['remarks'] ?? null,
            ]);

            // 9. Log activity logs
            $this->logActivity('cash_payment_selected', "Cash payment method selected for Booking {$booking->booking_number}.", $booking->id);
            $this->logActivity('booking_created', "Booking {$booking->booking_number} created for Customer: {$booking->customer->name}", $booking->id);
            $this->logActivity('receipt_generated', "Receipt {$receiptNumber} generated for Payment {$paymentNumber} (Pending Verification).", $booking->id);
            $this->logActivity('cash_collection_request_created', "Cash Collection Request {$ccr->collection_number} created.", $booking->id);

            return [
                'booking' => $booking,
                'transaction' => $transaction,
                'receipt' => $receipt,
                'cash_collection_request' => $ccr
            ];
        });
    }

    /**
     * Generate sequential unique collection numbers (e.g. CCR260000001)
     */
    public function generateCollectionNumber()
    {
        $year = now()->format('y'); // e.g. "26" for 2026
        $prefix = "CCR" . $year;

        $latest = \App\Models\CashCollectionRequest::where('collection_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latest) {
            return $prefix . "0000001";
        }

        $lastNumber = substr($latest->collection_number, 5);
        $nextNumber = str_pad((int)$lastNumber + 1, 7, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }
}
