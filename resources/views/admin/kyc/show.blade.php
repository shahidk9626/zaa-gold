@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-4 mb-4">
        <!-- Customer Profile Card -->
        <div class="card bg-white border shadow-sm text-dark p-4">
            <h5 class="text-primary font-weight-bold mb-4">Customer Details</h5>
            <div class="text-center mb-4">
                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; border: 2px solid #ddd;">
                    <i class="mdi mdi-account text-muted" style="font-size: 2.5rem;"></i>
                </div>
                <h4 class="mt-3 mb-1 text-dark font-weight-bold">{{ $kyc->user->name }}</h4>
                <span class="badge {{ $kyc->status === 'approved' ? 'badge-success' : ($kyc->status === 'rejected' ? 'badge-danger' : 'badge-warning text-dark') }} mb-3">
                    {{ ucfirst($kyc->status) }}
                </span>
            </div>

            <div class="mb-3">
                <label class="small text-muted d-block mb-1">Email Address</label>
                <span class="font-weight-bold">{{ $kyc->user->email }}</span>
            </div>

            <div class="mb-3">
                <label class="small text-muted d-block mb-1">Phone Number</label>
                <span class="font-weight-bold">{{ $kyc->user->phone ?? 'N/A' }}</span>
            </div>

            <div class="mb-3">
                <label class="small text-muted d-block mb-1">Document Type</label>
                <span class="font-weight-bold text-dark">{{ $kyc->document_type }}</span>
            </div>

            <div class="mb-3">
                <label class="small text-muted d-block mb-1">Document Number</label>
                <span class="font-weight-bold text-dark">{{ $kyc->document_number }}</span>
            </div>

            @if($kyc->rejected_reason)
                <div class="alert alert-danger mt-3 mb-0 py-2">
                    <h6 class="font-weight-bold mb-1"><i class="mdi mdi-alert-circle mr-1"></i> Rejection Reason:</h6>
                    <p class="mb-0 small text-dark">{{ $kyc->rejected_reason }}</p>
                </div>
            @endif

            @if($kyc->approved_by)
                <div class="mt-4 p-3 bg-light rounded border">
                    <label class="small text-muted d-block uppercase mb-1">Reviewed By</label>
                    <span class="font-weight-bold d-block text-dark">{{ $kyc->approver->name ?? 'System' }}</span>
                    <span class="small text-muted">{{ $kyc->approved_at ? $kyc->approved_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Documents Display Card -->
        <div class="card bg-white border shadow-sm text-dark p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h5 class="text-primary font-weight-bold mb-0">Identity Documents Review</h5>
                @if(hasPermission('kyc.download'))
                    <a href="{{ route('kyc.download', $kyc->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-download mr-1"></i> Download All Documents (ZIP)
                    </a>
                @endif
            </div>

            <!-- Documents Grid -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card bg-light border p-3 text-center">
                        <span class="font-weight-bold d-block mb-3 text-dark">Document Front Image</span>
                        <div class="border rounded p-1 bg-white" style="height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <img src="{{ asset('storage/' . $kyc->front_image) }}" class="w-100 h-100 object-cover" style="cursor: pointer;" onclick="viewImage('{{ asset('storage/' . $kyc->front_image) }}')">
                        </div>
                    </div>
                </div>

                @if($kyc->back_image)
                    <div class="col-md-6 mb-4">
                        <div class="card bg-light border p-3 text-center">
                            <span class="font-weight-bold d-block mb-3 text-dark">Document Back Image</span>
                            <div class="border rounded p-1 bg-white" style="height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <img src="{{ asset('storage/' . $kyc->back_image) }}" class="w-100 h-100 object-cover" style="cursor: pointer;" onclick="viewImage('{{ asset('storage/' . $kyc->back_image) }}')">
                            </div>
                        </div>
                    </div>
                @endif

                @if($kyc->selfie)
                    <div class="col-md-6 mb-4">
                        <div class="card bg-light border p-3 text-center">
                            <span class="font-weight-bold d-block mb-3 text-dark">Passport Size Photo</span>
                            <div class="border rounded p-1 bg-white" style="height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <img src="{{ asset('storage/' . $kyc->selfie) }}" class="w-100 h-100 object-cover" style="cursor: pointer;" onclick="viewImage('{{ asset('storage/' . $kyc->selfie) }}')">
                            </div>
                        </div>
                    </div>
                @endif

                @if($kyc->pan_card)
                    <div class="col-md-6 mb-4">
                        <div class="card bg-light border p-3 text-center">
                            <span class="font-weight-bold d-block mb-3 text-dark">PAN Card</span>
                            <div class="border rounded p-1 bg-white" style="height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                @if(Str::endsWith($kyc->pan_card, '.pdf'))
                                    <a href="{{ asset('storage/' . $kyc->pan_card) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-file-pdf mr-1"></i> View PAN PDF</a>
                                @else
                                    <img src="{{ asset('storage/' . $kyc->pan_card) }}" class="w-100 h-100 object-cover" style="cursor: pointer;" onclick="viewImage('{{ asset('storage/' . $kyc->pan_card) }}')">
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if($kyc->signature)
                    <div class="col-md-6 mb-4">
                        <div class="card bg-light border p-3 text-center">
                            <span class="font-weight-bold d-block mb-3 text-dark">Signature Scan</span>
                            <div class="border rounded p-1 bg-white" style="height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                @if(Str::endsWith($kyc->signature, '.pdf'))
                                    <a href="{{ asset('storage/' . $kyc->signature) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-file-pdf mr-1"></i> View Signature PDF</a>
                                @else
                                    <img src="{{ asset('storage/' . $kyc->signature) }}" class="w-100 h-100 object-cover" style="cursor: pointer;" onclick="viewImage('{{ asset('storage/' . $kyc->signature) }}')">
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if($kyc->additional_documents)
                    <div class="col-md-6 mb-4">
                        <div class="card bg-light border p-3 text-center">
                            <span class="font-weight-bold d-block mb-3 text-dark">Additional Documents</span>
                            <div class="border rounded p-1 bg-white" style="height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                @if(Str::endsWith($kyc->additional_documents, '.pdf'))
                                    <a href="{{ asset('storage/' . $kyc->additional_documents) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-file-pdf mr-1"></i> View PDF Scan</a>
                                @else
                                    <img src="{{ asset('storage/' . $kyc->additional_documents) }}" class="w-100 h-100 object-cover" style="cursor: pointer;" onclick="viewImage('{{ asset('storage/' . $kyc->additional_documents) }}')">
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Approval Controls -->
            @if(in_array($kyc->status, ['pending', 'resubmission_required']))
                <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                    @if(hasPermission('kyc.reject'))
                        <button class="btn btn-danger px-4 mr-2" data-toggle="modal" data-target="#rejectModal">
                            <i class="mdi mdi-close mr-1"></i> Reject / Request Update
                        </button>
                    @endif
                    @if(hasPermission('kyc.approve'))
                        <button class="btn btn-success px-4" onclick="approveKyc()">
                            <i class="mdi mdi-check mr-1"></i> Approve KYC
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Rejection Modal -->
@if(hasPermission('kyc.reject'))
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-white text-dark border">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title text-dark">Reject / Request Resubmission</h5>
                <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="rejectForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="action_type" class="text-dark font-weight-bold">Review Action <span class="text-danger">*</span></label>
                        <select name="action_type" id="action_type" class="form-control bg-white text-dark" required>
                            <option value="rejected">Reject Permanently</option>
                            <option value="resubmission_required">Request Resubmission</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="rejected_reason" class="text-dark font-weight-bold">Remarks / Reason <span class="text-danger">*</span></label>
                        <textarea name="rejected_reason" id="rejected_reason" rows="3" required class="form-control bg-white text-dark" placeholder="Specify why the documents are rejected or what needs correction..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" id="rejectSubmitBtn" class="btn btn-danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-hidden="true" style="background-color: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content bg-transparent border-0 text-center">
            <button type="button" class="close text-white absolute-top-right" data-dismiss="modal" aria-label="Close" style="font-size: 2.5rem; position: absolute; right: 15px; top: 15px; z-index: 10000;">
                <span aria-hidden="true">&times;</span>
            </button>
            <img id="previewImage" src="" class="img-fluid rounded" style="max-height: 80vh; border: 4px solid white;">
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function viewImage(src) {
        $('#previewImage').attr('src', src);
        $('#imagePreviewModal').modal('show');
    }

    function approveKyc() {
        Swal.fire({
            title: 'Approve KYC?',
            text: 'This will verify the customer identity and set verification status as verified.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2dce89',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, Approve!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('kyc.approve', $kyc->id) }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'KYC Approved',
                            text: response.success,
                            confirmButtonColor: '#3f50f6'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON.message || 'Approval failed.', 'error');
                    }
                });
            }
        });
    }

    $(document).ready(function () {
        $('#rejectForm').on('submit', function (e) {
            e.preventDefault();
            let rejectBtn = $('#rejectSubmitBtn');
            rejectBtn.prop('disabled', true).text('Rejecting...');

            $.ajax({
                url: "{{ route('kyc.reject', $kyc->id) }}",
                type: 'POST',
                data: $(this).serialize(),
                success: function (response) {
                    $('#rejectModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'KYC Rejected',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    rejectBtn.prop('disabled', false).text('Confirm Reject');
                    Swal.fire('Error', xhr.responseJSON.message || 'Rejection failed.', 'error');
                }
            });
        });
    });
</script>
@endpush
