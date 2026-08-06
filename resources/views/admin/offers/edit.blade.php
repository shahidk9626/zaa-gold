@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card bg-white text-dark border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Edit Promotional Offer</h4>
                        <p class="card-description text-muted">Update promo settings, discount rates, or plan associations</p>
                    </div>
                    <a href="{{ route('offers.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to List
                    </a>
                </div>

                <form id="offerForm" action="{{ route('offers.update', $offer->id) }}" method="POST" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    
                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">1. Basic Offer Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-4 form-group">
                            <label class="text-dark">Offer Code <span class="text-danger">*</span></label>
                            <input type="text" name="offer_code" value="{{ $offer->offer_code }}" required class="form-control bg-white text-dark" placeholder="e.g. DIWALI50" style="text-transform: uppercase;">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Offer Name <span class="text-danger">*</span></label>
                            <input type="text" name="offer_name" value="{{ $offer->offer_name }}" required class="form-control bg-white text-dark" placeholder="e.g. Diwali Special ₹50 OFF">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Status <span class="text-danger">*</span></label>
                            <select name="status" required class="form-control bg-white text-dark">
                                <option value="Draft" {{ $offer->status === 'Draft' ? 'selected' : '' }}>Draft</option>
                                <option value="Active" {{ $offer->status === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $offer->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="Expired" {{ $offer->status === 'Expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Start Date</label>
                            <input type="datetime-local" name="start_date" value="{{ $offer->start_date ? $offer->start_date->format('Y-m-d\TH:i') : '' }}" class="form-control bg-white text-dark">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">End Date</label>
                            <input type="datetime-local" name="end_date" value="{{ $offer->end_date ? $offer->end_date->format('Y-m-d\TH:i') : '' }}" class="form-control bg-white text-dark">
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="text-dark">Priority Order</label>
                            <input type="number" name="priority" value="{{ $offer->priority }}" min="0" class="form-control bg-white text-dark" placeholder="Higher priority is checked first">
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="text-dark">Upload New Offer Banner Image (Optional)</label>
                            <input type="file" name="banner_image" class="form-control bg-white text-dark">
                            <small class="text-muted">Allowed types: jpeg, png, jpg, gif. Max size: 2MB.</small>
                            @if($offer->banner)
                                <div class="mt-2">
                                    <p class="text-muted small mb-1">Current Banner:</p>
                                    <img src="{{ asset('storage/' . $offer->banner) }}" alt="Banner" style="height: 60px; object-fit: contain; border: 1px solid #ced4da; padding: 2px;">
                                </div>
                            @endif
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="text-dark">Applicable Gold Plans <span class="text-danger">*</span></label>
                            <select name="plans[]" class="form-control select2-plans" multiple="multiple" required style="width: 100%;">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ $offer->emiPlans->contains($plan->id) ? 'selected' : '' }}>{{ $plan->plan_name }} ({{ $plan->plan_code }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select one or more Gold Plans eligible for this offer.</small>
                        </div>

                        <div class="col-12 form-group">
                            <label class="text-dark">Offer Description</label>
                            <textarea name="offer_description" rows="3" class="form-control bg-white text-dark" placeholder="Describe the terms and benefits...">{{ $offer->offer_description }}</textarea>
                        </div>
                    </div>

                    <h5 class="text-primary font-weight-bold mb-3 border-bottom pb-2">2. Offer Type & Value</h5>
                    <div class="row mb-4">
                        <div class="col-md-4 form-group">
                            <label class="text-dark">Offer Type <span class="text-danger">*</span></label>
                            <select name="offer_type" id="offer_type" required class="form-control bg-white text-dark">
                                <option value="">Select Offer Type</option>
                                <option value="percentage" {{ $offer->offer_type === 'percentage' ? 'selected' : '' }}>Percentage Discount</option>
                                <option value="fixed" {{ $offer->offer_type === 'fixed' ? 'selected' : '' }}>Fixed Amount Discount</option>
                                <option value="emi" {{ $offer->offer_type === 'emi' ? 'selected' : '' }}>EMI Discount (Waiver)</option>
                            </select>
                        </div>

                        <!-- PERCENTAGE CONTAINER -->
                        <div class="col-md-8 form-group type-container" id="percentage_container" style="display: {{ $offer->offer_type === 'percentage' ? 'block' : 'none' }};">
                            <label class="text-dark">Discount Percentage (%) <span class="text-danger">*</span></label>
                            <input type="number" name="percentage" id="percentage" value="{{ $offer->percentage }}" min="0.01" max="100.00" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 10.00">
                            <small class="text-muted">Calculates discount based on Total Plan Value after GST and charges.</small>
                        </div>

                        <!-- FIXED CONTAINER -->
                        <div class="col-md-8 form-group type-container" id="fixed_container" style="display: {{ $offer->offer_type === 'fixed' ? 'block' : 'none' }};">
                            <label class="text-dark">Discount Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="fixed_amount" id="fixed_amount" value="{{ $offer->fixed_amount }}" min="0.01" step="0.01" class="form-control bg-white text-dark" placeholder="e.g. 1000.00">
                            <small class="text-muted">Directly deducts this fixed amount from final payable.</small>
                        </div>

                        <!-- EMI CONTAINER -->
                        <div class="col-md-8 type-container" id="emi_container" style="display: {{ $offer->offer_type === 'emi' ? 'block' : 'none' }};">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="text-dark">Required Paid EMIs <span class="text-danger">*</span></label>
                                    <input type="number" name="required_emi_count" id="required_emi_count" value="{{ $offer->required_emi_count }}" min="1" class="form-control bg-white text-dark" placeholder="e.g. 11">
                                    <small class="text-muted">Number of months customer pays.</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="text-dark">Free/Waived EMIs <span class="text-danger">*</span></label>
                                    <input type="number" name="free_emi_count" id="free_emi_count" value="{{ $offer->free_emi_count }}" min="1" class="form-control bg-white text-dark" placeholder="e.g. 1">
                                    <small class="text-muted">Number of waived months (complimentary).</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                        <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                            <i class="mdi mdi-check mr-1"></i> Update Offer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        // Initialize Select2
        $('.select2-plans').select2({
            placeholder: "Select applicable Gold Plans",
            allowClear: true,
            closeOnSelect: false
        });

        // Trigger dynamic type fields
        $('#offer_type').on('change', function () {
            let type = $(this).val();
            $('.type-container').hide().find('input').prop('required', false);
            
            if (type === 'percentage') {
                $('#percentage_container').show().find('input').prop('required', true);
            } else if (type === 'fixed') {
                $('#fixed_container').show().find('input').prop('required', true);
            } else if (type === 'emi') {
                $('#emi_container').show().find('input').prop('required', true);
            }
        });

        // Initialize required flags on load based on default type
        let initType = $('#offer_type').val();
        $('.type-container').find('input').prop('required', false);
        if (initType === 'percentage') {
            $('#percentage_container').find('input').prop('required', true);
        } else if (initType === 'fixed') {
            $('#fixed_container').find('input').prop('required', true);
        } else if (initType === 'emi') {
            $('#emi_container').find('input').prop('required', true);
        }

        // Validate offer code to be uppercase automatically
        $('input[name="offer_code"]').on('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        // AJAX Form Submission
        $('#offerForm').on('submit', function (e) {
            e.preventDefault();
            
            // Check HTML5 validation
            if (!this.checkValidity()) {
                this.reportValidity();
                return;
            }

            let submitBtn = $('#submitBtn');
            submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Saving...');

            let formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Promo Offer Updated',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    }).then(() => {
                        window.location.href = "{{ route('offers.index') }}";
                    });
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html('<i class="mdi mdi-check mr-1"></i> Update Offer');
                    let errors = xhr.responseJSON.errors;
                    let errorMsg = '';
                    if (errors) {
                        Object.keys(errors).forEach(key => {
                            errorMsg += errors[key][0] + '\n';
                        });
                    } else {
                        errorMsg = xhr.responseJSON.message || 'Something went wrong';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Failed',
                        text: errorMsg,
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });
    });
</script>
<style>
    /* Premium style matching for Select2 inside bootstrap 5 container */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da !important;
        border-radius: 0.25rem !important;
        min-height: 38px !important;
        background-color: #ffffff !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #3f50f6 !important;
        color: white !important;
        border: 1px solid #3f50f6 !important;
        border-radius: 4px !important;
        font-size: 0.8rem !important;
        padding: 2px 6px !important;
        margin-top: 6px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: white !important;
        margin-right: 5px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        background: transparent !important;
        color: #ff3ca6 !important;
    }
</style>
@endpush
