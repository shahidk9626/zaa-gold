<?php

namespace App\Http\Controllers\Customer;

use App\Models\GoldBooking;
use App\Models\GstInvoice;
use App\Http\Controllers\GoldBookingController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class CertificateController extends CustomerBaseController
{
    public function index(): View
    {
        $certificates = $this->customerService->getCertificates($this->customerId());

        return view('customer.certificates.index', [
            'priceLockCertificates' => $certificates['price_lock_certificates'],
            'gstInvoices' => $certificates['gst_invoices'],
        ]);
    }

    public function downloadPriceLock(int $bookingId): \Symfony\Component\HttpFoundation\Response
    {
        GoldBooking::where('customer_id', $this->customerId())->findOrFail($bookingId);

        return app(GoldBookingController::class)->downloadCertificate($bookingId);
    }

    public function previewPriceLock(int $bookingId): \Illuminate\View\View
    {
        $booking = GoldBooking::where('customer_id', $this->customerId())->findOrFail($bookingId);
        $certificate = $booking->certificate;

        if (!$certificate) {
            abort(404, 'Price Lock Certificate not found.');
        }

        $bookingService = app(\App\Services\BookingService::class);
        $data = $bookingService->getCertificateData($certificate);
        $data['is_preview'] = true;

        return view('admin.bookings.certificate-pdf', $data);
    }

    public function downloadInvoice(int $id): \Symfony\Component\HttpFoundation\Response
    {
        GstInvoice::where('customer_id', $this->customerId())->findOrFail($id);

        return app(InvoiceController::class)->downloadPdf($id);
    }
}
