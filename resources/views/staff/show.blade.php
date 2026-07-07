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
                        @if($staff->profile_image)
                            <img src="{{ asset('storage/' . $staff->profile_image) }}" class="w-100 h-100 object-cover" alt="profile_image" />
                        @else
                            <i class="mdi mdi-account text-muted" style="font-size: 3rem;"></i>
                        @endif
                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-1 text-dark font-weight-bold">{{ $staff->name }}</h4>
                    <p class="mb-0 text-muted">
                        {{ $staff->role->name ?? 'Staff Member' }} | Employee Code: <strong>{{ $staff->staffDetail->emp_code ?? 'N/A' }}</strong>
                    </p>
                </div>
                <div class="col-md-auto text-right mt-3 mt-md-0">
                    <div class="btn-group" role="group">
                        @if($prev)
                            <a href="{{ route('staff.show', $prev->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="mdi mdi-chevron-left mr-1"></i> Prev
                            </a>
                        @endif
                        @if($next)
                            <a href="{{ route('staff.show', $next->id) }}" class="btn btn-outline-secondary btn-sm">
                                Next <i class="mdi mdi-chevron-right ml-1"></i>
                            </a>
                        @endif
                        <a href="{{ route('staff.index') }}" class="btn btn-secondary btn-sm">
                            <i class="mdi mdi-arrow-left mr-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs border-0 bg-light border rounded p-1 mb-4" id="staffDetailTabs" role="tablist">
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
                <a class="nav-link" id="employment-tab" data-toggle="tab" href="#employment" role="tab" aria-controls="employment" aria-selected="false">
                    <i class="mdi mdi-briefcase mr-1"></i> Employment
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link" id="bank-tab" data-toggle="tab" href="#bank" role="tab" aria-controls="bank" aria-selected="false">
                    <i class="mdi mdi-bank mr-1"></i> Bank
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
            <div class="tab-content" id="staffDetailTabsContent">
                <!-- Personal Tab -->
                <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                    <h5 class="text-primary font-weight-bold mb-4">Personal Details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Full Name</label>
                            <span class="font-weight-bold text-dark">{{ $staff->name }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Email Address</label>
                            <span class="font-weight-bold text-dark">{{ $staff->email ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Phone Number</label>
                            <span class="font-weight-bold text-dark">{{ $staff->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Father's Name</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->father_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Mother's Name</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->mother_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Date of Birth</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->dob ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Gender</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->gender ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Marital Status</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->marital_status ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Contact Tab -->
                <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                    <h5 class="text-primary font-weight-bold mb-4">Contact Information</h5>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="small text-muted d-block uppercase">Full Address</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->address ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small text-muted d-block uppercase">City</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->city ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small text-muted d-block uppercase">State</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->state ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="small text-muted d-block uppercase">Pincode</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->pincode ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Country</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->country ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Employment Tab -->
                <div class="tab-pane fade" id="employment" role="tabpanel" aria-labelledby="employment-tab">
                    <h5 class="text-primary font-weight-bold mb-4">Employment Details</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Employee Code</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->emp_code ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Role</label>
                            <span class="font-weight-bold text-dark">{{ $staff->role->name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Department</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->department ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Designation</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->designation ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Joining Date</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->joining_date ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Salary</label>
                            <span class="font-weight-bold text-dark">
                                {{ $staff->staffDetail->salary ? '$' . number_format($staff->staffDetail->salary, 2) : 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bank Tab -->
                <div class="tab-pane fade" id="bank" role="tabpanel" aria-labelledby="bank-tab">
                    <h5 class="text-primary font-weight-bold mb-4">Bank & Financials</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Bank Name</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->bank_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Account Number</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->account_number ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">IFSC Code</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->ifsc_code ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">PAN Number</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->pan_number ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="small text-muted d-block uppercase">Aadhar Number</label>
                            <span class="font-weight-bold text-dark">{{ $staff->staffDetail->aadhar_number ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Documents Tab -->
                <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                    <h5 class="text-primary font-weight-bold mb-4">Uploaded Documents</h5>
                    <div class="row">
                        @forelse($staff->staffDocuments as $doc)
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                                <div class="bg-light p-3 rounded border text-center">
                                    <div class="bg-white border rounded p-3 mb-3 d-flex align-items-center justify-content-center" style="height: 100px;">
                                        @php $ext = pathinfo($doc->file_path, PATHINFO_EXTENSION); @endphp
                                        @if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif']))
                                            <img src="{{ asset('storage/' . $doc->file_path) }}" class="w-100 h-100 object-cover" style="max-height: 80px; border-radius: 0.25rem;">
                                        @else
                                            <i class="mdi mdi-file-pdf text-danger" style="font-size: 3rem;"></i>
                                        @endif
                                    </div>
                                    <p class="small font-weight-bold text-truncate text-dark mb-3" title="{{ $doc->document_name }}">
                                        {{ $doc->document_name }}
                                    </p>
                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-primary btn-sm btn-block">
                                        <i class="mdi mdi-eye"></i> View File
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 py-5 text-center bg-light rounded border">
                                <i class="mdi mdi-folder-open text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-dark mt-3 mb-0">No documents found for this staff member.</h5>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
