<x-customer-layout title="Certificates">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">Certificates & Invoices</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">Certificates</h5></div>

    <h5 class="mb-3">Price Lock Certificates</h5>
    <div class="row mb-4">
        @forelse($priceLockCertificates as $booking)
        <div class="col-md-4 grid-margin">
            <div class="card text-center p-4 h-100">
                <i class="mdi mdi-certificate text-primary" style="font-size: 3rem;"></i>
                <h6 class="mt-3">{{ $booking->product?->name }}</h6>
                <p class="text-muted small">{{ $booking->certificate?->certificate_number }}</p>
                <a href="{{ route('customer.certificates.price_lock', $booking->id) }}" class="btn btn-sm btn-primary mt-auto">Download PDF</a>
            </div>
        </div>
        @empty
        <div class="col-12"><p class="text-muted">No price lock certificates available.</p></div>
        @endforelse
    </div>

    <h5 class="mb-3">GST Invoices</h5>
    <div class="row">
        @forelse($gstInvoices as $invoice)
        <div class="col-md-4 grid-margin">
            <div class="card text-center p-4 h-100">
                <i class="mdi mdi-file-document text-success" style="font-size: 3rem;"></i>
                <h6 class="mt-3">{{ $invoice->invoice_number }}</h6>
                <p class="text-muted small">{{ $invoice->invoice_date?->format('d M Y') }}</p>
                <a href="{{ route('customer.certificates.invoice', $invoice->id) }}" class="btn btn-sm btn-primary mt-auto">Download PDF</a>
            </div>
        </div>
        @empty
        <div class="col-12"><p class="text-muted">No GST invoices available.</p></div>
        @endforelse
    </div>

    <div class="card mt-4 border-dashed">
        <div class="card-body text-center text-muted">
            <i class="mdi mdi-certificate-outline" style="font-size: 2rem;"></i>
            <p class="mb-0 mt-2">Completion Certificate — Coming Soon</p>
        </div>
    </div>
</x-customer-layout>
