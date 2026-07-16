<?php

namespace App\Services;

use App\Models\GoldBooking;
use App\Models\BookingPayment;
use App\Models\GstInvoice;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /**
     * Generate GST Invoice for a successful payment
     */
    public function generateInvoice(BookingPayment $payment)
    {
        // 1. Prevent duplicate invoices for the same payment
        $existing = GstInvoice::where('payment_id', $payment->id)->first();
        if ($existing) {
            throw new \Exception("An invoice already exists for this payment (Invoice: {$existing->invoice_number}).");
        }

        return DB::transaction(function () use ($payment) {
            $booking = GoldBooking::with(['customer.customerDetail', 'product', 'emiPlan'])->findOrFail($payment->booking_id);
            $customer = $booking->customer;

            // Generate unique serial invoice number
            $invoiceNumber = $this->generateInvoiceNumber();
            $verificationToken = Str::random(32);

            // Snapshot customer billing address
            $billingAddressParts = array_filter([
                $customer->customerDetail->address ?? null,
                $customer->customerDetail->city ?? null,
                $customer->customerDetail->state ?? null,
                $customer->customerDetail->pincode ?? null,
                $customer->customerDetail->country ?? null
            ]);
            $billingAddress = implode(', ', $billingAddressParts);
            if (empty($billingAddress)) {
                $billingAddress = 'N/A';
            }

            // Snapshot gold product details
            $productName = $booking->product->name ?? 'Gold Product';
            $goldWeight = (float)$booking->gold_weight;
            $goldPurity = (float)$booking->gold_purity;
            $lockedGoldPrice = (float)$booking->locked_price_per_gram;

            // Capture exact financial values from the payment
            $goldValue = (float)$payment->principal_paid;
            
            // Calculate proportional finance and storage charges
            $totalBookingCharges = (float)($booking->finance_charge_amount + $booking->storage_charge_amount);
            $financeCharge = 0.00;
            $storageCharge = 0.00;
            if ($totalBookingCharges > 0) {
                $financeCharge = round((float)$payment->interest_paid * ((float)$booking->finance_charge_amount / $totalBookingCharges), 2);
                $storageCharge = round((float)$payment->interest_paid * ((float)$booking->storage_charge_amount / $totalBookingCharges), 2);
            } else {
                $financeCharge = (float)$payment->interest_paid;
            }

            // GST details based on plan percentages
            $gstOnGoldPercent = (float)$booking->gst_on_gold_percent;
            $gstOnChargesPercent = (float)$booking->gst_on_charges_percent;

            $gstOnGoldAmount = round($goldValue * ($gstOnGoldPercent / 100), 2);
            $gstOnChargesAmount = round($storageCharge * ($gstOnChargesPercent / 100), 2);

            // Calculate subtotal (before tax) and grand total (with tax)
            $subtotal = $goldValue + $financeCharge + $storageCharge + (float)$payment->late_fee_paid;
            $grandTotal = $subtotal + $gstOnGoldAmount + $gstOnChargesAmount;

            $paymentReceived = (float)$payment->amount_paid;
            $balanceAmount = round(max(0, $grandTotal - $paymentReceived), 2);

            // Automate CGST / SGST / IGST breakdown based on state configurations
            $companyState = strtolower(trim(config('app.company_state', 'Maharashtra')));
            $customerState = strtolower(trim($customer->customerDetail->state ?? ''));

            $totalTaxAmount = $gstOnGoldAmount + $gstOnChargesAmount;
            
            // We calculate the effective tax percentage
            $effectiveTaxPercent = 0.00;
            if ($subtotal > 0) {
                $effectiveTaxPercent = round(($totalTaxAmount / $subtotal) * 100, 2);
            }

            $cgstPercent = 0.00;
            $cgstAmount = 0.00;
            $sgstPercent = 0.00;
            $sgstAmount = 0.00;
            $igstPercent = 0.00;
            $igstAmount = 0.00;

            if (empty($customerState) || $customerState === $companyState) {
                // Intra-state: CGST (50%) + SGST (50%)
                $cgstPercent = round($effectiveTaxPercent / 2, 2);
                $cgstAmount = round($totalTaxAmount / 2, 2);
                $sgstPercent = $cgstPercent;
                $sgstAmount = $totalTaxAmount - $cgstAmount; // avoid rounding deviation
            } else {
                // Inter-state: IGST (100%)
                $igstPercent = $effectiveTaxPercent;
                $igstAmount = $totalTaxAmount;
            }

            // Create GST Invoice record
            $invoice = new GstInvoice();
            $invoice->invoice_number = $invoiceNumber;
            $invoice->booking_id = $booking->id;
            $invoice->payment_id = $payment->id;
            $invoice->customer_id = $customer->id;
            $invoice->invoice_date = now();
            
            $invoice->customer_name = $customer->name;
            $invoice->customer_email = $customer->email;
            $invoice->customer_phone = $customer->customerDetail->phone_number ?? $customer->phone ?? 'N/A';
            $invoice->billing_address = $billingAddress;
            
            $invoice->product_name = $productName;
            $invoice->gold_weight = $goldWeight;
            $invoice->gold_purity = $goldPurity;
            $invoice->locked_gold_price = $lockedGoldPrice;
            
            $invoice->gold_value = $goldValue;
            $invoice->gst_on_gold_percent = $gstOnGoldPercent;
            $invoice->gst_on_gold_amount = $gstOnGoldAmount;
            
            $invoice->finance_charge = $financeCharge;
            $invoice->storage_charge = $storageCharge;
            $invoice->gst_on_charges_percent = $gstOnChargesPercent;
            $invoice->gst_on_charges_amount = $gstOnChargesAmount;
            
            $invoice->subtotal = $subtotal;
            $invoice->grand_total = $grandTotal;
            $invoice->payment_received = $paymentReceived;
            $invoice->balance_amount = $balanceAmount;
            
            $invoice->cgst_percent = $cgstPercent;
            $invoice->cgst_amount = $cgstAmount;
            $invoice->sgst_percent = $sgstPercent;
            $invoice->sgst_amount = $sgstAmount;
            $invoice->igst_percent = $igstPercent;
            $invoice->igst_amount = $igstAmount;
            
            $invoice->invoice_status = 'Generated';
            $invoice->verification_token = $verificationToken;
            $invoice->created_by_id = Auth::id() ?? $payment->created_by_id;
            $invoice->save();

            // Generate and save QR code
            $qrPath = $this->generateInvoiceQrCode($invoice);
            $invoice->qr_code = $qrPath;

            // Generate and save PDF
            $pdfPath = $this->generateInvoicePdf($invoice);
            $invoice->pdf_path = $pdfPath;
            $invoice->save();

            // Log activity
            $this->logActivityDirect('invoice_generated', "GST Invoice {$invoiceNumber} generated for Payment {$payment->payment_number}", $booking->id);

            return $invoice;
        });
    }

    /**
     * Cancel an invoice
     */
    public function cancelInvoice(GstInvoice $invoice, $remarks = null)
    {
        return DB::transaction(function () use ($invoice, $remarks) {
            $invoice->invoice_status = 'Cancelled';
            $invoice->remarks = $remarks;
            $invoice->save();

            $this->logActivityDirect('invoice_cancelled', "GST Invoice {$invoice->invoice_number} cancelled. Reason: " . ($remarks ?? 'None'), $invoice->booking_id);
            return $invoice;
        });
    }

    /**
     * Generate sequential unique invoice numbers (e.g. INV260000001)
     */
    public function generateInvoiceNumber()
    {
        $year = now()->format('y'); // e.g. "26" for 2026
        $prefix = "INV" . $year;

        $latest = GstInvoice::where('invoice_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        if (!$latest) {
            return $prefix . "0000001";
        }

        $lastNumber = substr($latest->invoice_number, 5);
        $nextNumber = str_pad((int)$lastNumber + 1, 7, '0', STR_PAD_LEFT);

        return $prefix . $nextNumber;
    }

    /**
     * Generate and cache QR Code
     */
    public function generateInvoiceQrCode(GstInvoice $invoice)
    {
        $verificationUrl = url("/admin/invoices/" . $invoice->id);
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verificationUrl);

        $qrFilename = "invoices/QR_" . $invoice->invoice_number . ".png";

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
     * Generate Invoice PDF and save it
     */
    public function generateInvoicePdf(GstInvoice $invoice)
    {
        $booking = $invoice->booking;
        $customer = $invoice->customer;
        $payment = $invoice->payment;
        $product = $booking->product;

        // Convert QR image to base64 for PDF rendering reliability
        $qrBase64 = '';
        if ($invoice->qr_code && Storage::disk('public')->exists($invoice->qr_code)) {
            $qrContent = Storage::disk('public')->get($invoice->qr_code);
            if (!empty($qrContent)) {
                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrContent);
            }
        }

        $pdfData = [
            'invoice' => $invoice,
            'booking' => $booking,
            'customer' => $customer,
            'payment' => $payment,
            'product' => $product,
            'plan' => $booking->emiPlan,
            'qrImageSrc' => $qrBase64,
            'amountInWords' => $this->convertAmountToWords($invoice->grand_total),
            'generatedAt' => now()->format('d M Y, h:i A'),
            'generatedBy' => auth()->user()->name ?? 'System'
        ];

        $pdf = Pdf::loadView('admin.invoices.pdf', $pdfData);
        $pdfPath = 'invoices/INV_' . $invoice->invoice_number . '.pdf';

        Storage::disk('public')->put($pdfPath, $pdf->output());

        return $pdfPath;
    }

    /**
     * Convert decimal amount to words
     */
    public function convertAmountToWords($number)
    {
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $hundred = null;
        $digits_length = strlen($no);
        $i = 0;
        $str = array();
        $words = array(
            0 => '', 1 => 'one', 2 => 'two',
            3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
            7 => 'seven', 8 => 'eight', 9 => 'nine',
            10 => 'ten', 11 => 'eleven', 12 => 'twelve',
            13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
            16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
            19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
            40 => 'forty', 50 => 'fifty', 60 => 'sixty',
            70 => 'seventy', 80 => 'eighty', 90 => 'ninety'
        );
        $digits = array('', 'hundred','thousand','lakh', 'crore');
        while( $i < $digits_length ) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
            } else $str[] = null;
        }
        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal > 0) ? "." . ($words[$decimal / 10] ?? $words[floor($decimal / 10) * 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
        return ($Rupees ? $Rupees . 'Rupees ' : '') . ($paise ? 'and ' . $paise : '') . ' Only';
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
