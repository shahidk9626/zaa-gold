@extends('layouts.app')

@section('content')
<div class="row">
    <!-- Latest Price Cards -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-dark font-weight-bold mb-0">Current Live Gold Prices</h5>
                    @if(hasPermission('gold-price.history'))
                        <a href="{{ route('gold-prices.history') }}" class="btn btn-outline-primary btn-sm">
                            <i class="mdi mdi-history mr-1"></i> View Audit History
                        </a>
                    @endif
                </div>
                <div class="row justify-content-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="bg-light p-4 rounded text-center border" style="border-left: 5px solid #d4af37 !important;">
                            <span class="text-muted small uppercase font-weight-bold">Today's 24K Bullion Price (per g)</span>
                            <h2 class="text-dark font-weight-bold mt-2" style="font-size: 2.2rem;">₹{{ $latestPrice ? number_format($latestPrice->price_24k, 2) : '0.00' }}</h2>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-4 rounded text-center border" style="border-left: 5px solid #c5a059 !important;">
                            <span class="text-muted small uppercase font-weight-bold">Today's 22K Bullion Price (per g)</span>
                            <h2 class="text-dark font-weight-bold mt-2" style="font-size: 2.2rem;">₹{{ $latestPrice ? number_format($latestPrice->price_22k, 2) : '0.00' }}</h2>
                        </div>
                    </div>
                </div>
                <div class="text-muted small mt-3 text-right">
                    Last Updated: <strong>{{ $latestPrice ? $latestPrice->effective_date->format('Y-m-d H:i:s') : 'N/A' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Left Column: Forms -->
    <div class="col-md-5 mb-4">
        <!-- Update Price Form Card -->
        @if(hasPermission('gold-price.create') || hasPermission('gold-price.edit'))
        <div class="card bg-white border shadow-sm mb-4">
            <div class="card-body">
                <h4 class="card-title text-dark">Update Gold Price</h4>
                <p class="card-description text-muted">Submit a new price rate configuration</p>

                <form id="priceForm" action="{{ $latestPrice ? route('gold-prices.update', $latestPrice->id) : route('gold-prices.store') }}" method="POST" class="mt-4">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="price_24k" class="text-dark">24K Gold Price (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price_24k" id="price_24k" required value="{{ $latestPrice->price_24k ?? '' }}" class="form-control bg-white text-dark" placeholder="e.g. 7200.00">
                    </div>

                    <div class="form-group mb-3">
                        <label for="price_22k" class="text-dark">22K Gold Price (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price_22k" id="price_22k" required value="{{ $latestPrice->price_22k ?? '' }}" class="form-control bg-white text-dark" placeholder="e.g. 6600.00">
                    </div>

                    <div class="form-group mb-3">
                        <label for="effective_date" class="text-dark">Effective Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="effective_date" id="effective_date" required value="{{ $latestPrice ? $latestPrice->effective_date->format('Y-m-d\TH:i') : date('Y-m-d\TH:i') }}" class="form-control bg-white text-dark">
                    </div>

                    <div class="form-group mb-4">
                        <label for="remarks" class="text-dark">Remarks / Source Description</label>
                        <input type="text" name="remarks" id="remarks" value="{{ $latestPrice->remarks ?? '' }}" class="form-control bg-white text-dark" placeholder="e.g. Market update, London Fix">
                    </div>

                    <input type="hidden" name="status" value="active">

                    <button type="submit" id="submitBtn" class="btn btn-primary btn-block w-100">
                        <i class="mdi mdi-check mr-1"></i> Update Prices & Log History
                    </button>
                </form>
            </div>
        </div>
        @endif

        <!-- Business Configuration Card -->
        @if(hasPermission('gold-price.edit'))
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-dark">Business Configuration</h4>
                <p class="card-description text-muted">Manage global limits and year definitions</p>

                <form id="settingsForm" action="{{ route('settings.update') }}" method="POST" class="mt-4">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="customer_max_purchase_grams" class="text-dark">Maximum Gold Purchase Per Customer (Grams) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="settings[customer_max_purchase_grams]" id="customer_max_purchase_grams" required value="{{ $settings['customer_max_purchase_grams'] ?? '100.00' }}" class="form-control bg-white text-dark" placeholder="e.g. 100">
                    </div>

                    <div class="form-group mb-4">
                        <label for="financial_year_period" class="text-dark">Financial Year Period <span class="text-muted">(Read-Only)</span></label>
                        <input type="text" id="financial_year_period" class="form-control bg-light text-dark" value="April - March" readonly disabled>
                    </div>

                    <button type="submit" id="settingsBtn" class="btn btn-success btn-block w-100 text-white">
                        <i class="mdi mdi-content-save mr-1"></i> Save Configuration
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>

    <!-- Price Configuration Log List Card -->
    <div class="col-md-7 mb-4">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-dark">Price Configurations List</h4>
                <p class="card-description text-muted">All active/inactive price configurations in database</p>

                <div class="table-responsive mt-3">
                    <table id="priceTable" class="table table-hover text-dark">
                        <thead>
                            <tr>
                                <th>Effective Date</th>
                                <th>24K Bullion</th>
                                <th>22K Bullion</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prices as $price)
                                <tr>
                                    <td class="align-middle font-weight-bold">
                                        {{ $price->effective_date->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="align-middle">₹{{ number_format($price->price_24k, 2) }}</td>
                                    <td class="align-middle">₹{{ number_format($price->price_22k, 2) }}</td>
                                    <td class="text-center align-middle">
                                        <span class="badge {{ $price->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $price->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Authorized Signature Card -->
        @if(hasPermission('gold-price.edit'))
        <div class="card bg-white border shadow-sm mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="card-title text-dark mb-0">Authorized Signature</h4>
                    @if($signatureUrl)
                        <span class="badge badge-success"><i class="mdi mdi-check-circle mr-1"></i> Active Signature</span>
                    @else
                        <span class="badge badge-secondary"><i class="mdi mdi-alert-circle mr-1"></i> System Default Text</span>
                    @endif
                </div>
                <p class="card-description text-muted">Upload the signature that will be used across all generated PDFs.</p>

                <!-- Current Signature Preview Section -->
                <div class="form-group mb-3">
                    <label class="text-dark font-weight-bold">Current Signature Preview</label>
                    <div id="signaturePreviewBox" class="p-3 border rounded text-center position-relative" style="background: repeating-conic-gradient(#f8f9fa 0% 25%, #ffffff 0% 50%) 50% / 16px 16px; min-height: 100px; display: flex; align-items: center; justify-content: center;">
                        @if($signatureUrl)
                            <img id="signaturePreviewImg" src="{{ $signatureUrl }}" alt="Authorized Signature" style="max-height: 80px; max-width: 260px; object-fit: contain;">
                        @else
                            <div id="signatureEmptyNotice" class="text-muted small">
                                <i class="mdi mdi-draw text-secondary d-block mb-1" style="font-size: 2rem;"></i>
                                No custom signature uploaded. Generated PDFs currently use system default signatory text.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Signature Upload Form -->
                <form id="signatureForm" action="{{ route('gold-prices.signature.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="signatureInput" class="text-dark font-weight-bold">
                            {{ $signatureUrl ? 'Replace / Update Signature' : 'Upload Signature Image' }}
                        </label>
                        <input type="file" name="signature" id="signatureInput" accept="image/png,image/jpeg,image/jpg,image/webp" class="form-control bg-white text-dark" style="height: auto;">
                        <small class="form-text text-muted mt-1">
                            Supported formats: <strong>PNG, JPG, JPEG, WEBP</strong> (Max size: 2MB).<br>
                            <span class="text-primary"><i class="mdi mdi-information-outline"></i> PNG with transparent background is strongly recommended for best PDF rendering.</span>
                        </small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" id="uploadSigBtn" class="btn btn-primary">
                            <i class="mdi mdi-upload mr-1"></i> {{ $signatureUrl ? 'Update Signature' : 'Upload & Save Signature' }}
                        </button>
                        @if($signatureUrl)
                            <button type="button" id="removeSigBtn" class="btn btn-outline-danger">
                                <i class="mdi mdi-delete mr-1"></i> Remove Signature
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('#priceForm').on('submit', function (e) {
            e.preventDefault();
            let submitBtn = $('#submitBtn');
            submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Prices Updated',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html('<i class="mdi mdi-check mr-1"></i> Update Prices & Log History');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error updating gold price',
                        text: xhr.responseJSON.error || 'Failed to update gold price configuration',
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });

        $('#settingsForm').on('submit', function (e) {
            e.preventDefault();
            let settingsBtn = $('#settingsBtn');
            settingsBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Configuration Saved',
                        text: response.success,
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    settingsBtn.prop('disabled', false).html('<i class="mdi mdi-content-save mr-1"></i> Save Configuration');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error saving settings',
                        text: xhr.responseJSON.error || 'Failed to update business configurations',
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });

        $('#signatureForm').on('submit', function (e) {
            e.preventDefault();
            let fileInput = $('#signatureInput')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No File Selected',
                    text: 'Please select an image file to upload.',
                    confirmButtonColor: '#3f50f6'
                });
                return;
            }

            let formData = new FormData(this);
            let btn = $('#uploadSigBtn');
            btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Uploading...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Signature Updated',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="mdi mdi-upload mr-1"></i> Upload & Save Signature');
                    let err = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.error) : 'Failed to upload signature.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        err = Object.values(xhr.responseJSON.errors).flat().join('\n');
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Error',
                        text: err,
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });

        $('#removeSigBtn').on('click', function () {
            Swal.fire({
                title: 'Remove Signature?',
                text: 'Generated PDFs will revert to using system default signatory text until a new signature is uploaded.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3ca6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Remove It'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('gold-prices.signature.remove') }}",
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Signature Removed',
                                text: response.success,
                                confirmButtonColor: '#3f50f6'
                            }).then(() => {
                                window.location.reload();
                            });
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON ? xhr.responseJSON.error : 'Failed to remove signature.',
                                confirmButtonColor: '#ff3ca6'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
