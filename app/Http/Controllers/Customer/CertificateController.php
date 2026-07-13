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

    public function downloadPriceLock(int $bookingId): BinaryFileResponse|RedirectResponse
    {
        GoldBooking::where('customer_id', $this->customerId())->findOrFail($bookingId);

        return app(GoldBookingController::class)->downloadCertificate($bookingId);
    }

    public function downloadInvoice(int $id): BinaryFileResponse
    {
        GstInvoice::where('customer_id', $this->customerId())->findOrFail($id);

        return app(InvoiceController::class)->downloadPdf($id);
    }
}
