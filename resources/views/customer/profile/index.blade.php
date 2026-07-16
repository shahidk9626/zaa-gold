<x-customer-layout title="Profile & Verification">
    <div class="page-header flex-wrap d-none d-md-flex">
        <h3 class="mb-0 font-weight-bold">My Profile & Verification</h3>
    </div>
    <div class="d-block d-md-none mb-3">
        <h5 class="font-weight-bold">Profile & KYC</h5>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="font-weight-bold mb-1">Please correct the errors below:</h6>
            <ul class="mb-0 pl-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    @endif

    {{-- Banner for Resubmission/Rejection --}}
    @if($latestKyc && in_array($kycStatus, ['Rejected', 'Resubmission Required']))
        <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert" style="border-radius: 8px;">
            <h6 class="alert-heading mb-1 font-weight-bold"><i class="mdi mdi-alert-circle mr-1"></i> Verification Action Required</h6>
            <p class="mb-1 small">The compliance team has requested updates to your KYC documents.</p>
            @if($latestKyc->rejected_reason)
                <p class="mb-0 small font-weight-bold">Remarks: <span class="font-weight-normal text-dark">"{{ $latestKyc->rejected_reason }}"</span></p>
            @endif
        </div>
    @endif

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs border-0 bg-light rounded p-1 mb-4" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active font-weight-bold" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="true">
                <i class="mdi mdi-account-card-details mr-1"></i> Profile Information
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link font-weight-bold" id="kyc-tab" data-toggle="tab" href="#kyc" role="tab" aria-controls="kyc" aria-selected="false">
                <i class="mdi mdi-shield-account mr-1"></i> KYC Documents
                @if($kycStatus === 'Approved')
                    <span class="badge badge-success ml-1">Verified</span>
                @elseif($kycStatus === 'Pending Review')
                    <span class="badge badge-warning text-dark ml-1">Pending</span>
                @elseif($kycStatus === 'Resubmission Required')
                    <span class="badge badge-info ml-1">Update</span>
                @endif
            </a>
        </li>
    </ul>

    <div class="tab-content pt-2">
        {{-- Profile Tab --}}
        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <form action="{{ route('customer.profile.update') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-lg-8">
                        {{-- 1. Identity & Contact --}}
                        <div class="card mb-4 bg-white border">
                            <div class="card-body">
                                <h5 class="card-title text-primary font-weight-bold mb-3"><i class="mdi mdi-account-circle mr-1"></i> Personal Identity & Contact</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label class="text-muted small">Full Name</label>
                                        <input type="text" class="form-control bg-light" value="{{ $user->name }}" disabled>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label class="text-muted small">Email Address</label>
                                        <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Primary Phone <span class="text-danger">*</span></label>
                                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>WhatsApp Number <span class="text-danger">*</span></label>
                                        <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date" name="dob" class="form-control" value="{{ old('dob', $user->customerDetail->dob ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Gender <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-control" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male" {{ old('gender', $user->customerDetail->gender ?? '') === 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ old('gender', $user->customerDetail->gender ?? '') === 'Female' ? 'selected' : '' }}>Female</option>
                                            <option value="Other" {{ old('gender', $user->customerDetail->gender ?? '') === 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Family & Nominee --}}
                        <div class="card mb-4 bg-white border">
                            <div class="card-body">
                                <h5 class="card-title text-primary font-weight-bold mb-3"><i class="mdi mdi-human-male-female mr-1"></i> Family, Nominee & Contacts</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Father's Name <span class="text-danger">*</span></label>
                                        <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $user->customerDetail->father_name ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Mother's Name</label>
                                        <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $user->customerDetail->mother_name ?? '') }}">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Nominee Name <span class="text-danger">*</span></label>
                                        <input type="text" name="nominee_name" class="form-control" value="{{ old('nominee_name', $user->customerDetail->nominee_name ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Emergency Contact Number <span class="text-danger">*</span></label>
                                        <input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $user->customerDetail->emergency_contact ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Alternate Number</label>
                                        <input type="text" name="alternate_number" class="form-control" value="{{ old('alternate_number', $user->customerDetail->alternate_number ?? '') }}">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Marital Status</label>
                                        <select name="marital_status" class="form-control">
                                            <option value="">Select Status</option>
                                            <option value="Single" {{ old('marital_status', $user->customerDetail->marital_status ?? '') === 'Single' ? 'selected' : '' }}>Single</option>
                                            <option value="Married" {{ old('marital_status', $user->customerDetail->marital_status ?? '') === 'Married' ? 'selected' : '' }}>Married</option>
                                            <option value="Divorced" {{ old('marital_status', $user->customerDetail->marital_status ?? '') === 'Divorced' ? 'selected' : '' }}>Divorced</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 3. Address details --}}
                        <div class="card mb-4 bg-white border">
                            <div class="card-body">
                                <h5 class="card-title text-primary font-weight-bold mb-3"><i class="mdi mdi-map-marker-radius mr-1"></i> Address Details</h5>
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Full Address <span class="text-danger">*</span></label>
                                        <textarea name="address" class="form-control" rows="2" required>{{ old('address', $user->customerDetail->address ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>City <span class="text-danger">*</span></label>
                                        <input type="text" name="city" class="form-control" value="{{ old('city', $user->customerDetail->city ?? '') }}" required>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>State <span class="text-danger">*</span></label>
                                        <input type="text" name="state" class="form-control" value="{{ old('state', $user->customerDetail->state ?? '') }}" required>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Country <span class="text-danger">*</span></label>
                                        <input type="text" name="country" class="form-control" value="{{ old('country', $user->customerDetail->country ?? 'India') }}" required>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Pincode <span class="text-danger">*</span></label>
                                        <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $user->customerDetail->pincode ?? '') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 4. Professional & Identity --}}
                        <div class="card mb-4 bg-white border">
                            <div class="card-body">
                                <h5 class="card-title text-primary font-weight-bold mb-3"><i class="mdi mdi-briefcase mr-1"></i> Professional & Government ID</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Occupation <span class="text-danger">*</span></label>
                                        <input type="text" name="occupation" class="form-control" value="{{ old('occupation', $user->customerDetail->occupation ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Annual Income</label>
                                        <input type="text" name="annual_income" class="form-control" value="{{ old('annual_income', $user->customerDetail->annual_income ?? '') }}">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>PAN Card Number <span class="text-danger">*</span></label>
                                        <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number', $user->customerDetail->pan_number ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Aadhaar Card Number <span class="text-danger">*</span></label>
                                        <input type="text" name="aadhar_number" class="form-control" value="{{ old('aadhar_number', $user->customerDetail->aadhar_number ?? '') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 5. Bank details --}}
                        <div class="card mb-4 bg-white border">
                            <div class="card-body">
                                <h5 class="card-title text-primary font-weight-bold mb-3"><i class="mdi mdi-bank mr-1"></i> Bank Account Information</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Bank Name <span class="text-danger">*</span></label>
                                        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $user->customerDetail->bank_name ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Account Number <span class="text-danger">*</span></label>
                                        <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $user->customerDetail->account_number ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>IFSC Code <span class="text-danger">*</span></label>
                                        <input type="text" name="ifsc_code" class="form-control" value="{{ old('ifsc_code', $user->customerDetail->ifsc_code ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Branch Name <span class="text-danger">*</span></label>
                                        <input type="text" name="branch" class="form-control" value="{{ old('branch', $user->customerDetail->branch ?? '') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <button type="submit" class="btn btn-primary btn-mobile-lg px-5 py-2 font-weight-bold">
                                <i class="mdi mdi-content-save mr-1"></i> Save Profile Details
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- KYC Tab --}}
        <div class="tab-pane fade" id="kyc" role="tabpanel" aria-labelledby="kyc-tab">
            <div class="row">
                <div class="col-lg-8">
                    @if(!$user->profile_completed)
                        <div class="alert alert-warning border shadow-sm p-4">
                            <div class="text-center mb-3">
                                <i class="mdi mdi-account-alert text-warning" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="text-center font-weight-bold text-dark">Profile Completion Required</h5>
                            <p class="text-center mb-0 small text-muted">Please complete and save your Profile Information details in the first tab before uploading your KYC documents.</p>
                        </div>
                    @else
                        <form action="{{ route('customer.profile.submit_kyc') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card mb-4 bg-white border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                                        <h5 class="card-title text-primary font-weight-bold mb-0"><i class="mdi mdi-cloud-upload mr-1"></i> Upload KYC Documents</h5>
                                        <div>
                                            @if($kycStatus === 'Approved')
                                                <span class="badge badge-success font-weight-bold px-3 py-2"><i class="mdi mdi-check-circle mr-1"></i> KYC Approved</span>
                                            @elseif($kycStatus === 'Pending Review')
                                                <span class="badge badge-warning text-dark font-weight-bold px-3 py-2"><i class="mdi mdi-clock mr-1"></i> Under Review</span>
                                            @elseif($kycStatus === 'Rejected')
                                                <span class="badge badge-danger font-weight-bold px-3 py-2"><i class="mdi mdi-close-circle mr-1"></i> Rejected</span>
                                            @elseif($kycStatus === 'Resubmission Required')
                                                <span class="badge badge-info font-weight-bold px-3 py-2"><i class="mdi mdi-alert-circle mr-1"></i> Resubmission Requested</span>
                                            @else
                                                <span class="badge badge-secondary font-weight-bold px-3 py-2">Not Submitted</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if(in_array($kycStatus, ['Approved', 'Pending Review']))
                                        <div class="alert alert-info py-3 mb-4">
                                            <p class="mb-0 small"><i class="mdi mdi-information-outline mr-1"></i> Documents cannot be modified while verification is pending or has been approved. If you need to make changes, please contact compliance support.</p>
                                        </div>
                                    @endif

                                    @php
                                        $isDisabled = in_array($kycStatus, ['Approved', 'Pending Review']) ? 'disabled' : '';
                                    @endphp

                                    <div class="row">
                                        {{-- Document 1: PAN Card --}}
                                        <div class="col-md-6 form-group mb-4">
                                            <label class="font-weight-bold text-dark">PAN Card <span class="text-danger">*</span></label>
                                            <input type="file" name="pan_card" class="form-control-file border p-2 w-100 rounded" {{ $isDisabled }}>
                                            <small class="text-muted d-block mt-1">Upload a clear photo or PDF scan of your PAN Card (Max 2MB).</small>
                                            @if($latestKyc && $latestKyc->pan_card)
                                                <div class="mt-2 small d-flex align-items-center">
                                                    <span class="text-success"><i class="mdi mdi-file-check mr-1"></i> Document Uploaded</span>
                                                    <a href="{{ asset('storage/' . $latestKyc->pan_card) }}" target="_blank" class="ml-3 font-weight-bold text-primary"><i class="mdi mdi-eye mr-1"></i> View</a>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Document 2: Aadhaar Front --}}
                                        <div class="col-md-6 form-group mb-4">
                                            <label class="font-weight-bold text-dark">Aadhaar Front Image <span class="text-danger">*</span></label>
                                            <input type="file" name="front_image" class="form-control-file border p-2 w-100 rounded" {{ $isDisabled }}>
                                            <small class="text-muted d-block mt-1">Upload the front side of your Aadhaar Card (Max 2MB).</small>
                                            @if($latestKyc && $latestKyc->front_image)
                                                <div class="mt-2 small d-flex align-items-center">
                                                    <span class="text-success"><i class="mdi mdi-file-check mr-1"></i> Document Uploaded</span>
                                                    <a href="{{ asset('storage/' . $latestKyc->front_image) }}" target="_blank" class="ml-3 font-weight-bold text-primary"><i class="mdi mdi-eye mr-1"></i> View</a>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Document 3: Aadhaar Back --}}
                                        <div class="col-md-6 form-group mb-4">
                                            <label class="font-weight-bold text-dark">Aadhaar Back Image <span class="text-danger">*</span></label>
                                            <input type="file" name="back_image" class="form-control-file border p-2 w-100 rounded" {{ $isDisabled }}>
                                            <small class="text-muted d-block mt-1">Upload the back side of your Aadhaar Card (Max 2MB).</small>
                                            @if($latestKyc && $latestKyc->back_image)
                                                <div class="mt-2 small d-flex align-items-center">
                                                    <span class="text-success"><i class="mdi mdi-file-check mr-1"></i> Document Uploaded</span>
                                                    <a href="{{ asset('storage/' . $latestKyc->back_image) }}" target="_blank" class="ml-3 font-weight-bold text-primary"><i class="mdi mdi-eye mr-1"></i> View</a>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Document 4: Passport Photo --}}
                                        <div class="col-md-6 form-group mb-4">
                                            <label class="font-weight-bold text-dark">Passport Size Photo <span class="text-danger">*</span></label>
                                            <input type="file" name="selfie" class="form-control-file border p-2 w-100 rounded" {{ $isDisabled }}>
                                            <small class="text-muted d-block mt-1">Upload a passport-sized profile picture or selfie (Max 2MB).</small>
                                            @if($latestKyc && $latestKyc->selfie)
                                                <div class="mt-2 small d-flex align-items-center">
                                                    <span class="text-success"><i class="mdi mdi-file-check mr-1"></i> Document Uploaded</span>
                                                    <a href="{{ asset('storage/' . $latestKyc->selfie) }}" target="_blank" class="ml-3 font-weight-bold text-primary"><i class="mdi mdi-eye mr-1"></i> View</a>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Document 5: Signature --}}
                                        <div class="col-md-6 form-group mb-4">
                                            <label class="font-weight-bold text-dark">Signature Scan <span class="text-danger">*</span></label>
                                            <input type="file" name="signature" class="form-control-file border p-2 w-100 rounded" {{ $isDisabled }}>
                                            <small class="text-muted d-block mt-1">Upload a scan or picture of your signature on a white paper (Max 2MB).</small>
                                            @if($latestKyc && $latestKyc->signature)
                                                <div class="mt-2 small d-flex align-items-center">
                                                    <span class="text-success"><i class="mdi mdi-file-check mr-1"></i> Document Uploaded</span>
                                                    <a href="{{ asset('storage/' . $latestKyc->signature) }}" target="_blank" class="ml-3 font-weight-bold text-primary"><i class="mdi mdi-eye mr-1"></i> View</a>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Document 6: Additional --}}
                                        <div class="col-md-6 form-group mb-4">
                                            <label class="font-weight-bold text-dark">Additional Documents <span class="text-muted">(Optional)</span></label>
                                            <input type="file" name="additional_documents" class="form-control-file border p-2 w-100 rounded" {{ $isDisabled }}>
                                            <small class="text-muted d-block mt-1">Upload any supplementary identification or proof (Max 4MB).</small>
                                            @if($latestKyc && $latestKyc->additional_documents)
                                                <div class="mt-2 small d-flex align-items-center">
                                                    <span class="text-success"><i class="mdi mdi-file-check mr-1"></i> Document Uploaded</span>
                                                    <a href="{{ asset('storage/' . $latestKyc->additional_documents) }}" target="_blank" class="ml-3 font-weight-bold text-primary"><i class="mdi mdi-eye mr-1"></i> View</a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if(!$isDisabled)
                                <div class="mb-4">
                                    <button type="submit" class="btn btn-success btn-mobile-lg px-5 py-2 font-weight-bold text-white">
                                        <i class="mdi mdi-cloud-upload mr-1"></i> Submit KYC Verification
                                    </button>
                                </div>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-customer-layout>
