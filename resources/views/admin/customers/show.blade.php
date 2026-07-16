@extends('layouts.app')

@section('content')
<style>
    .profile-header-cover {
        height: 180px;
        background: linear-gradient(135deg, #3f50f6 0%, #ff3ca6 100%);
        border-radius: 1rem;
        position: relative;
        overflow: hidden;
    }
    .profile-header-card {
        margin-top: -60px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 1rem;
    }
    .profile-image-container {
        width: 100px;
        height: 100px;
        border-radius: 1rem;
        overflow: hidden;
        border: 4px solid #ffffff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .nav-tabs .nav-link {
        border: 0;
        background: transparent;
        font-weight: bold;
        color: #8392ab !important;
        border-radius: 0.5rem;
        padding: 0.75rem 1.5rem;
    }
    .nav-tabs .nav-link.active {
        background: linear-gradient(135deg, #3f50f6 0%, #ff3ca6 100%) !important;
        color: white !important;
        box-shadow: 0 4px 10px rgba(63, 80, 246, 0.2);
    }
    .tab-content {
        background: transparent;
        border: 0;
    }
</style>

<div class="row">
    <div class="col-12">
        <!-- Cover Background -->
        <div class="profile-header-cover mb-4"></div>

        <!-- Profile Header Details -->
        <div class="card profile-header-card text-dark p-4 mb-4 shadow-sm">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="profile-image-container bg-light d-flex align-items-center justify-content-center">
                        @if($customer->profile_image)
                            <img src="{{ asset('storage/' . $customer->profile_image) }}" class="w-100 h-100 object-cover" alt="profile_image" />
                        @else
                            <i class="mdi mdi-account text-muted" style="font-size: 3rem;"></i>
                        @endif
                    </div>
                </div>
                <div class="col-md-5">
                    <h4 class="mb-1 text-dark font-weight-bold">{{ $customer->name }}</h4>
                    <p class="mb-0 text-muted">
                        Role: <strong>Customer</strong> | Referred By: <strong>{{ $customer->referredBy ? ($customer->referredBy->name . ' (' . ($customer->referredBy->staffDetail->emp_code ?? 'N/A') . ')') : 'None' }}</strong>
                    </p>
                </div>
                <div class="col-md-4 border-left pl-md-4 mt-3 mt-md-0">
                    <h6 class="font-weight-bold text-dark mb-2"><i class="mdi mdi-scale-balance text-warning mr-1"></i> FY Gold Purchase Limit</h6>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Purchased:</span>
                        <span class="font-weight-bold text-dark">{{ number_format($purchaseLimit['purchased'], 2) }} / {{ number_format($purchaseLimit['limit'], 2) }} g</span>
                    </div>
                    <div class="progress mb-2" style="height: 6px; border-radius: 3px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ min(100, $purchaseLimit['percentage']) }}%;" aria-valuenow="{{ $purchaseLimit['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Remaining Limit:</span>
                        <span class="font-weight-bold text-success">{{ number_format($purchaseLimit['remaining'], 2) }} g ({{ number_format($purchaseLimit['percentage'], 1) }}%)</span>
                    </div>
                </div>
                <div class="col-md-auto text-right mt-3 mt-md-0">
                    <div class="btn-group" role="group">
                        @if($prev)
                            <a href="{{ route('customers.show', $prev->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="mdi mdi-chevron-left mr-1"></i> Prev
                            </a>
                        @endif
                        @if($next)
                            <a href="{{ route('customers.show', $next->id) }}" class="btn btn-outline-secondary btn-sm">
                                Next <i class="mdi mdi-chevron-right ml-1"></i>
                            </a>
                        @endif
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
                            <i class="mdi mdi-arrow-left mr-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs border-0 bg-light border rounded p-1 mb-4" id="customerDetailTabs" role="tablist">
            <li class="nav-item flex-fill text-center">
                <a class="nav-link active" id="personal-tab" data-toggle="tab" href="#personal" role="tab" aria-controls="personal" aria-selected="true">
                    <i class="mdi mdi-account mr-1"></i> Personal
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" aria-selected="false">
                    <i class="mdi mdi-map-marker mr-1"></i> Contact
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="occupation-tab" data-toggle="tab" href="#occupation" role="tab" aria-controls="occupation" aria-selected="false">
                    <i class="mdi mdi-briefcase mr-1"></i> Occupation & Bank
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="documents-tab" data-toggle="tab" href="#documents" role="tab" aria-controls="documents" aria-selected="false">
                    <i class="mdi mdi-file-document mr-1"></i> Documents
                </a>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="card bg-white text-dark border shadow-sm p-4">
            <div class="tab-content" id="customerDetailTabsContent">
                <!-- Personal Tab -->
                <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                    <h5 class="text-primary font-weight-bold mb-4">Personal Details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Full Name</label>
                            <span class="font-weight-bold text-dark">{{ $customer->name }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Father's Name</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->father_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Mother's Name</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->mother_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Nominee Name</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->nominee_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Date of Birth</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->dob ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Gender</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->gender ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Marital Status</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->marital_status ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Contact Tab -->
                <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                    <h5 class="text-primary font-weight-bold mb-4">Contact Information</h5>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="small text-muted d-block">Full Address</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->address ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small text-muted d-block">City</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->city ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small text-muted d-block">State</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->state ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small text-muted d-block">Pincode</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->pincode ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Country</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->country ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">WhatsApp Number</label>
                            <span class="font-weight-bold text-dark">{{ $customer->whatsapp_number ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Alternate Phone</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->alternate_number ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Occupation & Bank Tab -->
                <div class="tab-pane fade" id="occupation" role="tabpanel" aria-labelledby="occupation-tab">
                    <h5 class="text-primary font-weight-bold mb-4">Occupation & Bank details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Occupation</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->occupation ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Annual Income</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->annual_income ?? 'N/A' }}</span>
                        </div>
                        
                        <div class="col-12 my-2"><hr style="border-color: rgba(0,0,0,0.1);"></div>

                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Bank Name</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->bank_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Account Number</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->account_number ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">IFSC Code</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->ifsc_code ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Branch</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->branch ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">PAN Number</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->pan_number ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block">Aadhar Number</label>
                            <span class="font-weight-bold text-dark">{{ $customer->customerDetail->aadhar_number ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Documents Tab -->
                <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                    <h5 class="text-primary font-weight-bold mb-4">KYC Documents</h5>
                    <div class="row">
                        @forelse($customer->customerDocuments as $doc)
                            <div class="col-md-6 mb-3">
                                <div class="bg-light p-3 rounded d-flex justify-content-between align-items-center border">
                                    <div class="text-truncate mr-2">
                                        <i class="mdi mdi-file-document text-primary mr-2" style="font-size: 1.5rem; vertical-align: middle;"></i>
                                        <span class="font-weight-bold text-dark">{{ $doc->document_name }}</span>
                                        <small class="text-muted d-block text-truncate">{{ $doc->file_original_name }}</small>
                                    </div>
                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-primary btn-sm px-3">
                                        <i class="mdi mdi-download mr-1"></i> View
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-4">
                                <i class="mdi mdi-file-document-box-outline text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No documents uploaded for this customer.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
