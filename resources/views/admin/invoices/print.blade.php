@php
    $booking = $invoice->booking;
    $customer = $invoice->customer;
    $payment = $invoice->payment;
    $product = $booking->product;
    $plan = $booking->emiPlan;
    
    // Convert QR code path to dynamic storage URL for browser loading
    $qrImageSrc = '';
    if ($invoice->qr_code && \Illuminate\Support\Facades\Storage::disk('public')->exists($invoice->qr_code)) {
        $qrImageSrc = asset('storage/' . $invoice->qr_code);
    }
    
    $amountInWords = app(\App\Services\InvoiceService::class)->convertAmountToWords($invoice->grand_total);
    $generatedAt = $invoice->created_at->format('d M Y, h:i A');
    $generatedBy = $invoice->creator->name ?? 'System';
    $isPrint = true;
@endphp
@include('admin.invoices.pdf', [
    'invoice' => $invoice,
    'booking' => $booking,
    'customer' => $customer,
    'payment' => $payment,
    'product' => $product,
    'plan' => $plan,
    'qrImageSrc' => $qrImageSrc,
    'amountInWords' => $amountInWords,
    'generatedAt' => $generatedAt,
    'generatedBy' => $generatedBy,
    'isPrint' => true
])
<script>
    // Trigger print dialog automatically after document is loaded in print preview
    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            window.print();
        }, 500);
    });
</script>
