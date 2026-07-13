<?php

namespace App\Http\Controllers;

use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use App\Models\ActivityLog;

class ReceiptController extends Controller
{
    /**
     * Show list of receipts (successful payments)
     */
    public function index(Request $request)
    {
        $query = BookingPayment::with(['booking.customer', 'emiSchedule'])
            ->where('status', 'Paid')
            ->latest('payment_date');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', '%' . $search . '%')
                  ->orWhere('payment_number', 'like', '%' . $search . '%')
                  ->orWhereHas('booking', function ($bq) use ($search) {
                      $bq->where('booking_number', 'like', '%' . $search . '%')
                         ->orWhereHas('customer', function ($cq) use ($search) {
                             $cq->where('name', 'like', '%' . $search . '%');
                         });
                  });
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        return view('admin.receipts.index', compact('payments'));
    }

    /**
     * Download the receipt PDF
     */
    public function downloadReceiptPdf($payment_id)
    {
        $payment = BookingPayment::with(['booking.customer', 'booking.product', 'booking.emiPlan', 'emiSchedule'])->findOrFail($payment_id);
        
        $qrBase64 = $this->generateReceiptQrCode($payment);

        $pdfData = [
            'payment' => $payment,
            'booking' => $payment->booking,
            'customer' => $payment->booking->customer,
            'product' => $payment->booking->product,
            'plan' => $payment->booking->emiPlan,
            'schedule' => $payment->emiSchedule,
            'qrImageSrc' => $qrBase64,
            'generatedAt' => now()->format('d M Y, h:i A'),
            'generatedBy' => auth()->user()->name ?? 'System'
        ];

        // Log receipt download activity
        $this->logActivityDirect('receipt_downloaded', "Receipt PDF {$payment->receipt_number} downloaded for Booking {$payment->booking->booking_number}", $payment->booking_id);

        $pdf = Pdf::loadView('admin.receipts.pdf', $pdfData);
        
        return $pdf->download("Receipt_{$payment->receipt_number}.pdf");
    }

    /**
     * Generate base64 QR code for receipt verification link
     */
    protected function generateReceiptQrCode(BookingPayment $payment)
    {
        $verificationUrl = route('payments.show', $payment->id);
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verificationUrl);

        try {
            $response = Http::timeout(5)->get($qrUrl);
            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
        } catch (\Exception $e) {
            // fallback
        }
        return '';
    }

    /**
     * Direct logging inside ActivityLog schema
     */
    protected function logActivityDirect($action, $description, $recordId)
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

        ActivityLog::create([
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
}
