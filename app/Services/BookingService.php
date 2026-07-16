<?php

namespace App\Services;

use App\Models\GoldBooking;
use App\Models\PriceLockCertificate;
use App\Models\Product;
use App\Models\EmiPlan;
use App\Models\GoldPrice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class BookingService
{
    protected $pricingService;
    protected $emiService;
    protected $paymentService;

    public function __construct(ProductPricingService $pricingService, EmiCalculationService $emiService, PaymentService $paymentService)
    {
        $this->pricingService = $pricingService;
        $this->emiService = $emiService;
        $this->paymentService = $paymentService;
    }

    /**
     * Create a new gold booking
     */
    public function createBooking($customerId, $productId, $emiPlanId, $remarks = null)
    {
        return DB::transaction(function () use ($customerId, $productId, $emiPlanId, $remarks) {
            $product = Product::findOrFail($productId);
            $plan = EmiPlan::findOrFail($emiPlanId);
            $customer = User::findOrFail($customerId);

            // 1. Calculate prices and plans
            $productPrice = $this->pricingService->calculateCurrentProductPrice($product);
            $latestPrice = GoldPrice::where('status', 'active')->latest('effective_date')->first() 
                ?? GoldPrice::latest('effective_date')->first();

            $is22k = strtoupper($product->gold_type) === '22K';
            $pricePerGram = $latestPrice ? ($is22k ? $latestPrice->price_22k : $latestPrice->price_24k) : 0.00;

            $calculations = $this->emiService->calculate($plan, $productPrice);

            // 2. Create the Booking
            $booking = new GoldBooking();
            $booking->booking_number = $this->generateBookingNumber();
            $booking->customer_id = $customerId;
            $booking->product_id = $productId;
            $booking->emi_plan_id = $emiPlanId;
            $booking->gold_price_id = $latestPrice ? $latestPrice->id : null;
            $booking->gold_weight = $product->weight_in_grams;
            $booking->gold_purity = $product->purity;
            $booking->locked_price_per_gram = $pricePerGram;
            $booking->locked_gold_value = $productPrice;

            $booking->gst_on_gold_percent = $plan->gst_on_gold_enabled ? ($plan->gst_on_gold_percent ?? 3.00) : 0.00;
            $booking->gst_on_gold_amount = $calculations['gst_on_gold'] ?? 0.00;

            $booking->finance_charge_percent = ($plan->finance_charge_enabled && strtolower($plan->finance_charge_type) === 'percentage') ? $plan->finance_charge_value : 0.00;
            $booking->finance_charge_amount = $calculations['finance_charge'] ?? 0.00;

            $booking->storage_charge_percent = ($plan->storage_charge_enabled && strtolower($plan->storage_charge_type) === 'percentage') ? $plan->storage_charge_value : 0.00;
            $booking->storage_charge_amount = $calculations['storage_charge'] ?? 0.00;

            $booking->gst_on_charges_percent = $plan->gst_on_charges_enabled ? ($plan->gst_on_charges_percent ?? 18.00) : 0.00;
            $booking->gst_on_charges_amount = $calculations['gst_on_charges'] ?? 0.00;

            $booking->grand_total = $calculations['total_payable'];
            $booking->monthly_emi = $calculations['installment'];
            $booking->duration_months = $plan->duration_months;
            $booking->booking_date = now();
            $booking->estimated_completion_date = $calculations['completion_date'];
            $booking->status = 'Booked'; // Initial status
            $booking->remarks = $remarks;
            $booking->created_by_id = auth()->id();
            $booking->updated_by_id = auth()->id();
            $booking->save();

            // Log activities under the existing Activity Log schema
            $this->logBookingActivity('booking_created', "Booking {$booking->booking_number} created for Customer: {$customer->name}", $booking->id);
            $this->logBookingActivity('price_locked', "Price locked at ₹" . number_format($pricePerGram, 2) . "/g for Booking {$booking->booking_number}", $booking->id);

            // 3. Generate Price Lock Certificate
            $this->generateCertificate($booking);

            // 4. Generate EMI Schedule
            $this->paymentService->generateScheduleForBooking($booking);

            // 5. Automatically pay the first EMI
            $this->paymentService->processFirstEmiPayment($booking);

            // Refresh booking to reload status and relationships
            $booking->refresh();

            return $booking;
        });
    }

    /**
     * Generate consecutive unique booking numbers (e.g. ZG26000001)
     */
    public function generateBookingNumber()
    {
        $year = now()->format('y'); // e.g. "26" for 2026
        $prefix = "ZG" . $year;

        $latest = GoldBooking::where('booking_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latest) {
            return $prefix . "000001";
        }

        $lastNumber = substr($latest->booking_number, 4);
        $nextNumber = str_pad((int)$lastNumber + 1, 6, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    /**
     * Generate consecutive unique certificate numbers (e.g. PLC26000001)
     */
    public function generateCertificateNumber()
    {
        $year = now()->format('y'); // e.g. "26" for 2026
        $prefix = "PLC" . $year;

        $latest = PriceLockCertificate::where('certificate_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latest) {
            return $prefix . "000001";
        }

        $lastNumber = substr($latest->certificate_number, 5);
        $nextNumber = str_pad((int)$lastNumber + 1, 6, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    /**
     * Generate price lock certificate records, PDFs and QR codes
     */
    public function generateCertificate(GoldBooking $booking)
    {
        $certificate = new PriceLockCertificate();
        $certificate->certificate_number = $this->generateCertificateNumber();
        $certificate->booking_id = $booking->id;
        $certificate->customer_id = $booking->customer_id;
        $certificate->issued_at = now();
        $certificate->locked_price = $booking->locked_price_per_gram;
        $certificate->gold_weight = $booking->gold_weight;
        $certificate->grand_total = $booking->grand_total;
        $certificate->verification_token = Str::random(32);
        $certificate->created_by_id = auth()->id();
        $certificate->save();

        // Generate QR code png
        $qrPath = $this->generateQrCode($certificate);
        $certificate->qr_code = $qrPath;

        // Render PDF
        $pdfPath = $this->generateCertificatePdf($certificate);
        $certificate->pdf_path = $pdfPath;
        $certificate->save();

        // Log certificate activity
        $this->logBookingActivity('certificate_generated', "Price Lock Certificate {$certificate->certificate_number} generated for Booking {$booking->booking_number}", $booking->id);

        return $certificate;
    }

    /**
     * Fetch verification QR Code image and save locally
     */
    public function generateQrCode(PriceLockCertificate $certificate)
    {
        $verificationUrl = url("/admin/bookings/verify/" . $certificate->verification_token);
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verificationUrl);

        $qrFilename = "qrcodes/PLC_" . $certificate->certificate_number . ".png";

        try {
            $response = Http::timeout(10)->get($qrUrl);
            if ($response->successful()) {
                Storage::disk('public')->put($qrFilename, $response->body());
            } else {
                Storage::disk('public')->put($qrFilename, '');
            }
        } catch (\Exception $e) {
            Storage::disk('public')->put($qrFilename, '');
        }

        return $qrFilename;
    }

    /**
     * Generate the certificate PDF document using DomPDF
     */
    public function generateCertificatePdf(PriceLockCertificate $certificate)
    {
        $booking = $certificate->goldBooking;
        $customer = $booking->customer;
        $product = $booking->product;
        $plan = $booking->emiPlan;

        $latestPrice = GoldPrice::find($booking->gold_price_id);
        $pricePerGram = $booking->locked_price_per_gram;

        $calculations = [
            'gold_value' => $booking->locked_gold_value,
            'gst_on_gold' => $booking->gst_on_gold_amount,
            'finance_charge' => $booking->finance_charge_amount,
            'storage_charge' => $booking->storage_charge_amount,
            'gst_on_charges' => $booking->gst_on_charges_amount,
            'total_payable' => $booking->grand_total,
            'installment' => $booking->monthly_emi,
            'completion_date' => $booking->estimated_completion_date->format('Y-m-d'),
            'use_financial_engine' => (bool)($booking->gst_on_gold_amount > 0 || $booking->finance_charge_amount > 0 || $booking->storage_charge_amount > 0),
        ];

        // Format QR image content as base64 for PDF rendering reliability
        $qrBase64 = '';
        if ($certificate->qr_code && Storage::disk('public')->exists($certificate->qr_code)) {
            $qrContent = Storage::disk('public')->get($certificate->qr_code);
            if (!empty($qrContent)) {
                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrContent);
            }
        }

        $pdfData = [
            'certificate' => $certificate,
            'booking' => $booking,
            'customer' => $customer,
            'product' => $product,
            'plan' => $plan,
            'pricePerGram' => $pricePerGram,
            'calculations' => $calculations,
            'qrImageSrc' => $qrBase64,
            'generatedAt' => now()->format('d M Y, h:i A'),
            'generatedBy' => auth()->user()->name ?? 'System'
        ];

        $pdf = Pdf::loadView('admin.bookings.certificate-pdf', $pdfData);
        $pdfPath = 'certificates/PLC_' . $certificate->certificate_number . '.pdf';

        Storage::disk('public')->put($pdfPath, $pdf->output());

        return $pdfPath;
    }

    /**
     * Safely updates booking status and inserts status history records
     */
    public function changeStatus(GoldBooking $booking, $newStatus, $remarks = null)
    {
        $oldStatus = $booking->status;
        if ($oldStatus === $newStatus) {
            return $booking;
        }

        $booking->status = $newStatus;
        $booking->status_change_remarks = $remarks;
        $booking->save();

        $this->logBookingActivity('status_changed', "Booking status changed from {$oldStatus} to {$newStatus} for {$booking->booking_number}", $booking->id);

        return $booking;
    }

    /**
     * Write logs inside the ActivityLog schema
     */
    protected function logBookingActivity($action, $description, $recordId)
    {
        $userAgent = request()->header('User-Agent');
        $browser = 'Unknown';
        if (!empty($userAgent)) {
            if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) $browser = 'Internet Explorer';
            elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
            elseif (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
            elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
            elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) $browser = 'Opera';
            elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
        }

        \App\Models\ActivityLog::create([
            'module_name' => 'gold_booking',
            'record_id' => $recordId,
            'action_type' => $action,
            'old_data' => null,
            'new_data' => null,
            'description' => $description,
            'created_by_id' => auth()->id() ?? 1,
            'ip_address' => request()->ip(),
            'browser' => $browser,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Get start and end date of the financial year for a given date.
     */
    public function getFinancialYearDates(\DateTimeInterface $date = null): array
    {
        $carbonDate = $date ? \Carbon\Carbon::instance($date) : \Carbon\Carbon::now();
        $year = $carbonDate->year;
        
        $startMonth = (int) \App\Models\SystemSetting::get('financial_year_start_month', 4);
        $startDay = (int) \App\Models\SystemSetting::get('financial_year_start_day', 1);

        if ($carbonDate->month < $startMonth || ($carbonDate->month == $startMonth && $carbonDate->day < $startDay)) {
            $start = \Carbon\Carbon::create($year - 1, $startMonth, $startDay, 0, 0, 0);
            $end = $start->copy()->addYear()->subSecond();
        } else {
            $start = \Carbon\Carbon::create($year, $startMonth, $startDay, 0, 0, 0);
            $end = $start->copy()->addYear()->subSecond();
        }

        return [$start, $end];
    }

    /**
     * Calculate the total purchased gold weight in grams for a customer in the current financial year.
     */
    public function getPurchasedWeightForFinancialYear(int $customerId, \DateTimeInterface $date = null): float
    {
        list($start, $end) = $this->getFinancialYearDates($date);

        return (float) GoldBooking::where('customer_id', $customerId)
            ->whereIn('status', ['Booked', 'Active', 'Completed'])
            ->whereBetween('booking_date', [$start, $end])
            ->sum('gold_weight');
    }

    /**
     * Get the remaining gold purchase limit in grams for a customer in the current financial year.
     */
    public function getRemainingPurchaseLimit(int $customerId, \DateTimeInterface $date = null): float
    {
        $maxLimit = (float) \App\Models\SystemSetting::get('customer_max_purchase_grams', 100.00);
        $purchased = $this->getPurchasedWeightForFinancialYear($customerId, $date);

        return max(0.00, $maxLimit - $purchased);
    }

    /**
     * Check if a customer can purchase the specified weight of gold in the current financial year.
     */
    public function canPurchaseGold(int $customerId, float $newPurchaseWeight, \DateTimeInterface $date = null): bool
    {
        $remaining = $this->getRemainingPurchaseLimit($customerId, $date);
        return $newPurchaseWeight <= $remaining;
    }
}
