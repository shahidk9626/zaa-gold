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

    {{-- KYC Approved Banner --}}
    @if($isKycApproved)
        <div class="alert alert-success border-0 shadow-sm mb-4 d-flex justify-content-between align-items-center flex-wrap" role="alert" style="border-radius: 8px;">
            <div>
                <h6 class="alert-heading mb-1 font-weight-bold"><i class="mdi mdi-shield-check mr-1"></i> KYC Verified Account</h6>
                <p class="mb-0 small">Your KYC is fully verified and approved. Direct profile updates are disabled for security. To update your profile information, please submit a Profile Update Request for Admin review.</p>
            </div>
            <div class="mt-2 mt-sm-0">
                @if($hasPendingRequest)
                    <button class="btn btn-warning text-dark font-weight-bold disabled" disabled>
                        <i class="mdi mdi-clock-outline mr-1"></i> Update Request Pending
                    </button>
                @else
                    <button type="button" class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#requestProfileUpdateModal">
                        <i class="mdi mdi-account-edit mr-1"></i> Request Profile Update
                    </button>
                @endif
            </div>
        </div>
    @endif

    @if($hasPendingRequest)
        <div class="alert alert-warning border shadow-sm mb-4" role="alert">
            <i class="mdi mdi-information mr-1"></i> <strong>Notice:</strong> You already have a profile update request pending with our compliance team. New requests cannot be submitted until your pending request is reviewed.
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
        <li class="nav-item" role="presentation">
            <a class="nav-link font-weight-bold" id="requests-tab" data-toggle="tab" href="#requests" role="tab" aria-controls="requests" aria-selected="false">
                <i class="mdi mdi-history mr-1"></i> Profile Update Requests
                @if($profileUpdateRequests->where('status', 'Pending')->count() > 0)
                    <span class="badge badge-warning text-dark ml-1">{{ $profileUpdateRequests->where('status', 'Pending')->count() }}</span>
                @endif
            </a>
        </li>
    </ul>

    <div class="tab-content pt-2">
        {{-- TAB 1: Profile Information --}}
        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <div class="row">
                <div class="col-lg-8">
                    @php
                        $inputDisabled = $isKycApproved ? 'disabled' : '';
                    @endphp
                    <form action="{{ route('customer.profile.update') }}" method="POST">
                        @csrf
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
                                        <input type="text" name="phone" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('phone', $user->phone) }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>WhatsApp Number <span class="text-danger">*</span></label>
                                        <input type="text" name="whatsapp_number" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date" name="dob" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('dob', $user->customerDetail->dob ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Gender <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" required {{ $inputDisabled }}>
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
                                        <input type="text" name="father_name" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('father_name', $user->customerDetail->father_name ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Mother's Name</label>
                                        <input type="text" name="mother_name" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('mother_name', $user->customerDetail->mother_name ?? '') }}" {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Nominee Name <span class="text-danger">*</span></label>
                                        <input type="text" name="nominee_name" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('nominee_name', $user->customerDetail->nominee_name ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Emergency Contact Number <span class="text-danger">*</span></label>
                                        <input type="text" name="emergency_contact" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('emergency_contact', $user->customerDetail->emergency_contact ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Alternate Number</label>
                                        <input type="text" name="alternate_number" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('alternate_number', $user->customerDetail->alternate_number ?? '') }}" {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Marital Status</label>
                                        <select name="marital_status" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" {{ $inputDisabled }}>
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
                                        <textarea name="address" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" rows="2" required {{ $inputDisabled }}>{{ old('address', $user->customerDetail->address ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>City <span class="text-danger">*</span></label>
                                        <input type="text" name="city" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('city', $user->customerDetail->city ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>State <span class="text-danger">*</span></label>
                                        <input type="text" name="state" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('state', $user->customerDetail->state ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Country <span class="text-danger">*</span></label>
                                        <input type="text" name="country" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('country', $user->customerDetail->country ?? 'India') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label>Pincode <span class="text-danger">*</span></label>
                                        <input type="text" name="pincode" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('pincode', $user->customerDetail->pincode ?? '') }}" required {{ $inputDisabled }}>
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
                                        <input type="text" name="occupation" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('occupation', $user->customerDetail->occupation ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Annual Income</label>
                                        <input type="text" name="annual_income" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('annual_income', $user->customerDetail->annual_income ?? '') }}" {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>PAN Card Number <span class="text-danger">*</span></label>
                                        <input type="text" name="pan_number" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('pan_number', $user->customerDetail->pan_number ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Aadhaar Card Number <span class="text-danger">*</span></label>
                                        <input type="text" name="aadhar_number" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('aadhar_number', $user->customerDetail->aadhar_number ?? '') }}" required pattern="[0-9]{12}" maxlength="12" title="Please enter a valid 12-digit Aadhaar Card Number" {{ $inputDisabled }}>
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
                                        <input type="text" name="bank_name" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('bank_name', $user->customerDetail->bank_name ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Account Number <span class="text-danger">*</span></label>
                                        <input type="text" name="account_number" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('account_number', $user->customerDetail->account_number ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>IFSC Code <span class="text-danger">*</span></label>
                                        <input type="text" name="ifsc_code" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('ifsc_code', $user->customerDetail->ifsc_code ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Branch Name <span class="text-danger">*</span></label>
                                        <input type="text" name="branch" class="form-control {{ $inputDisabled ? 'bg-light' : '' }}" value="{{ old('branch', $user->customerDetail->branch ?? '') }}" required {{ $inputDisabled }}>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(!$isKycApproved)
                            <div class="mb-4">
                                <button type="submit" class="btn btn-primary btn-mobile-lg px-5 py-2 font-weight-bold">
                                    <i class="mdi mdi-content-save mr-1"></i> Save Profile Details
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
                
                {{-- Profile Image Card --}}
                <div class="col-lg-4">
                    <div class="card mb-4 bg-white border">
                        <div class="card-body">
                            <h5 class="card-title text-primary font-weight-bold mb-3">
                                <i class="mdi mdi-account-circle mr-1"></i> Profile Photo
                            </h5>
                            
                            <div class="d-flex flex-column align-items-center">
                                <!-- Photo Container -->
                                <div class="position-relative mb-3 border rounded bg-light d-flex justify-content-center align-items-center" style="width: 150px; height: 150px; overflow: hidden; border-radius: 8px;">
                                    <img id="customer-avatar-preview" src="{{ $user->profile_image_url }}" alt="avatar" class="w-100 h-100 object-cover">
                                </div>

                                <!-- Actions -->
                                @if($user->profile_image)
                                    <form method="POST" action="{{ route('customer.profile.image.remove') }}" class="mb-3 w-100">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm btn-block font-weight-bold">
                                            <i class="mdi mdi-delete-forever mr-1"></i> Remove Photo
                                        </button>
                                    </form>
                                @endif

                                <!-- Upload Form -->
                                <form method="POST" action="{{ route('customer.profile.image.upload') }}" enctype="multipart/form-data" class="w-100">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label class="text-muted small">Select new photo (Max 2MB)</label>
                                        <input type="file" name="profile_image" accept="image/*" class="form-control-file border p-2 rounded w-100" required onchange="previewCustomerAvatar(this)">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold">
                                        <i class="mdi mdi-cloud-upload mr-1"></i> Upload Photo
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function previewCustomerAvatar(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('customer-avatar-preview').src = e.target.result;
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>

        {{-- TAB 2: KYC Documents --}}
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
                                            <p class="mb-0 small"><i class="mdi mdi-information-outline mr-1"></i> Documents cannot be modified while verification is pending or has been approved. If you need to make changes, please submit a Profile Update Request.</p>
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
                                            <label class="font-weight-bold text-dark">Passport Size Photo <span class="text-muted">(Optional)</span></label>
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
                                            <label class="font-weight-bold text-dark">Signature Scan <span class="text-muted">(Optional)</span></label>
                                            <input type="file" name="signature" class="form-control-file border p-2 w-100 rounded" {{ $isDisabled }}>
                                            <small class="text-muted d-block mt-1">Upload a scan or picture of your signature on a white paper (Max 2MB).</small>
                                            @if($latestKyc && $latestKyc->signature)
                                                <div class="mt-2 small d-flex align-items-center">
                                                    <span class="text-success"><i class="mdi mdi-file-check mr-1"></i> Document Uploaded</span>
                                                    <a href="{{ asset('storage/' . $latestKyc->signature) }}" target="_blank" class="ml-3 font-weight-bold text-primary"><i class="mdi mdi-eye mr-1"></i> View</a>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Document 7: Bank Document / Cancelled Cheque --}}
                                        <div class="col-md-6 form-group mb-4">
                                            <label class="font-weight-bold text-dark">Bank Document / Cancelled Cheque <span class="text-danger">*</span></label>
                                            <input type="file" name="bank_document" class="form-control-file border p-2 w-100 rounded" {{ $isDisabled }}>
                                            <small class="text-muted d-block mt-1">Upload bank statement page showing details or a cancelled cheque (Max 2MB).</small>
                                            @if($latestKyc && $latestKyc->bank_document)
                                                <div class="mt-2 small d-flex align-items-center">
                                                    <span class="text-success"><i class="mdi mdi-file-check mr-1"></i> Document Uploaded</span>
                                                    <a href="{{ asset('storage/' . $latestKyc->bank_document) }}" target="_blank" class="ml-3 font-weight-bold text-primary"><i class="mdi mdi-eye mr-1"></i> View</a>
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

        {{-- TAB 3: Profile Update Requests History --}}
        <div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests-tab">
            <div class="card mb-4 bg-white border shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h5 class="card-title text-primary font-weight-bold mb-0">
                            <i class="mdi mdi-history mr-1"></i> Profile Update Requests History
                        </h5>
                        @if($isKycApproved && !$hasPendingRequest)
                            <button type="button" class="btn btn-primary btn-sm font-weight-bold" data-toggle="modal" data-target="#requestProfileUpdateModal">
                                <i class="mdi mdi-plus-circle mr-1"></i> Request Profile Update
                            </button>
                        @endif
                    </div>

                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table table-bordered table-striped text-dark" style="white-space: nowrap; width: 100%;">
                            <thead class="bg-light text-dark">
                                <tr>
                                    <th class="font-weight-bold">Request Date</th>
                                    <th class="font-weight-bold">Requested Changes</th>
                                    <th class="font-weight-bold">Reason</th>
                                    <th class="font-weight-bold text-center">Status</th>
                                    <th class="font-weight-bold">Admin Remark</th>
                                    <th class="font-weight-bold">Reviewed Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($profileUpdateRequests as $req)
                                    <tr>
                                        <td>{{ $req->created_at ? $req->created_at->format('d/m/Y h:i A') : 'N/A' }}</td>
                                        <td>
                                            @if(is_array($req->requested_changes) && count($req->requested_changes) > 0)
                                                <ul class="list-unstyled mb-0 small">
                                                    @foreach($req->requested_changes as $chg)
                                                        <li class="mb-1">
                                                            <strong class="text-dark">{{ $chg['field_label'] ?? $chg['field_name'] }}:</strong>
                                                            <span class="text-danger strike mr-1"><del>{{ $chg['current_value'] ?? '' }}</del></span>
                                                            <i class="mdi mdi-arrow-right text-muted"></i>
                                                            <span class="text-success font-weight-bold">{{ $chg['requested_value'] ?? '' }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-dark small" title="{{ $req->reason }}">{{ Str::limit($req->reason, 50) }}</span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $stBadge = 'badge-secondary';
                                                if($req->status === 'Pending') $stBadge = 'badge-warning text-dark';
                                                elseif($req->status === 'Approved') $stBadge = 'badge-success';
                                                elseif($req->status === 'Rejected') $stBadge = 'badge-danger';
                                            @endphp
                                            <span class="badge {{ $stBadge }} font-weight-bold px-3 py-2">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($req->admin_remark)
                                                <span class="text-dark small">{{ $req->admin_remark }}</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $req->reviewed_at ? $req->reviewed_at->format('d/m/Y h:i A') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="mdi mdi-information-outline mr-1"></i> No profile update requests submitted yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Request Profile Update --}}
    @if($isKycApproved && !$hasPendingRequest)
        <div class="modal fade" id="requestProfileUpdateModal" tabindex="-1" role="dialog" aria-labelledby="requestProfileUpdateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form action="{{ route('customer.profile.request_update') }}" method="POST">
                        @csrf
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title font-weight-bold" id="requestProfileUpdateModalLabel">
                                <i class="mdi mdi-account-edit mr-1"></i> Request Profile Update
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-dark" style="max-height: 70vh; overflow-y: auto;">
                            <div class="alert alert-info py-2 mb-3 small">
                                <i class="mdi mdi-information-outline mr-1"></i> Modify the details you wish to change. Your request will be submitted to the Compliance Team for approval.
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Primary Phone</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">WhatsApp Number</label>
                                    <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $user->whatsapp_number) }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Father's Name</label>
                                    <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $user->customerDetail->father_name ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Mother's Name</label>
                                    <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $user->customerDetail->mother_name ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Nominee Name</label>
                                    <input type="text" name="nominee_name" class="form-control" value="{{ old('nominee_name', $user->customerDetail->nominee_name ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Emergency Contact Number</label>
                                    <input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $user->customerDetail->emergency_contact ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Alternate Number</label>
                                    <input type="text" name="alternate_number" class="form-control" value="{{ old('alternate_number', $user->customerDetail->alternate_number ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Marital Status</label>
                                    <select name="marital_status" class="form-control">
                                        <option value="">Select Status</option>
                                        <option value="Single" {{ old('marital_status', $user->customerDetail->marital_status ?? '') === 'Single' ? 'selected' : '' }}>Single</option>
                                        <option value="Married" {{ old('marital_status', $user->customerDetail->marital_status ?? '') === 'Married' ? 'selected' : '' }}>Married</option>
                                        <option value="Divorced" {{ old('marital_status', $user->customerDetail->marital_status ?? '') === 'Divorced' ? 'selected' : '' }}>Divorced</option>
                                    </select>
                                </div>
                                <div class="col-12 form-group">
                                    <label class="font-weight-bold small">Full Address</label>
                                    <textarea name="address" class="form-control" rows="2">{{ old('address', $user->customerDetail->address ?? '') }}</textarea>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label class="font-weight-bold small">City</label>
                                    <input type="text" name="city" class="form-control" value="{{ old('city', $user->customerDetail->city ?? '') }}">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label class="font-weight-bold small">State</label>
                                    <input type="text" name="state" class="form-control" value="{{ old('state', $user->customerDetail->state ?? '') }}">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label class="font-weight-bold small">Country</label>
                                    <input type="text" name="country" class="form-control" value="{{ old('country', $user->customerDetail->country ?? 'India') }}">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label class="font-weight-bold small">Pincode</label>
                                    <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $user->customerDetail->pincode ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Occupation</label>
                                    <input type="text" name="occupation" class="form-control" value="{{ old('occupation', $user->customerDetail->occupation ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Annual Income</label>
                                    <input type="text" name="annual_income" class="form-control" value="{{ old('annual_income', $user->customerDetail->annual_income ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">PAN Card Number</label>
                                    <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number', $user->customerDetail->pan_number ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Aadhaar Card Number</label>
                                    <input type="text" name="aadhar_number" class="form-control" value="{{ old('aadhar_number', $user->customerDetail->aadhar_number ?? '') }}" maxlength="12">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Bank Name</label>
                                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $user->customerDetail->bank_name ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Account Number</label>
                                    <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $user->customerDetail->account_number ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">IFSC Code</label>
                                    <input type="text" name="ifsc_code" class="form-control" value="{{ old('ifsc_code', $user->customerDetail->ifsc_code ?? '') }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small">Branch Name</label>
                                    <input type="text" name="branch" class="form-control" value="{{ old('branch', $user->customerDetail->branch ?? '') }}">
                                </div>
                            </div>

                            <div class="form-group border-top pt-3 mt-2">
                                <label class="font-weight-bold text-dark">Reason for Profile Update <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="3" required placeholder="Please explain why you want to update your profile details."></textarea>
                                <small class="text-muted">A valid reason is required for compliance review.</small>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary px-4 font-weight-bold" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                                <i class="mdi mdi-send mr-1"></i> Submit Update Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</x-customer-layout>
