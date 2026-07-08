@extends('layouts.app')

@section('content')
<style>
    .step-icon-active {
        background: linear-gradient(135deg, #3f50f6 0%, #ff3ca6 100%) !important;
        color: #fff !important;
        border-color: transparent !important;
        box-shadow: 0 4px 10px rgba(63, 80, 246, 0.3) !important;
    }
    .step-line-active {
        background: linear-gradient(135deg, #3f50f6 0%, #ff3ca6 100%) !important;
    }
    .step-num {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: bold;
        transition: all 0.3s;
        border: 2px solid rgba(0,0,0,0.1);
    }
    .step-tab span {
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
        margin-top: 0.5rem;
        transition: all 0.3s;
    }
</style>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card bg-white text-dark border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title text-dark">Edit Customer</h4>
                        <p class="card-description text-muted">Update customer onboarding profile and KYC records</p>
                    </div>
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to List
                    </a>
                </div>

                <!-- Step Navigation -->
                <div class="position-relative mb-5 mt-4">
                    <div class="d-flex justify-content-between align-items-center w-100 px-2">
                        <!-- Step 1 -->
                        <div class="step-tab d-flex flex-column align-items-center z-index-10 cursor-pointer" data-step="1">
                            <div class="step-num bg-secondary text-muted">1</div>
                            <span class="text-muted">Referral</span>
                        </div>

                        <!-- Line 1-2 -->
                        <div class="step-line flex-grow-1 mx-2" style="height: 3px; background-color: rgba(0,0,0,0.1);" data-line="1"></div>

                        <!-- Step 2 -->
                        <div class="step-tab d-flex flex-column align-items-center z-index-10 cursor-default opacity-50" data-step="2">
                            <div class="step-num bg-secondary text-muted">2</div>
                            <span class="text-muted">Personal</span>
                        </div>

                        <!-- Line 2-3 -->
                        <div class="step-line flex-grow-1 mx-2" style="height: 3px; background-color: rgba(0,0,0,0.1);" data-line="2"></div>

                        <!-- Step 3 -->
                        <div class="step-tab d-flex flex-column align-items-center z-index-10 cursor-default opacity-50" data-step="3">
                            <div class="step-num bg-secondary text-muted">3</div>
                            <span class="text-muted">Contact</span>
                        </div>

                        <!-- Line 3-4 -->
                        <div class="step-line flex-grow-1 mx-2" style="height: 3px; background-color: rgba(0,0,0,0.1);" data-line="3"></div>

                        <!-- Step 4 -->
                        <div class="step-tab d-flex flex-column align-items-center z-index-10 cursor-default opacity-50" data-step="4">
                            <div class="step-num bg-secondary text-muted">4</div>
                            <span class="text-muted">Occupation</span>
                        </div>

                        <!-- Line 4-5 -->
                        <div class="step-line flex-grow-1 mx-2" style="height: 3px; background-color: rgba(0,0,0,0.1);" data-line="4"></div>

                        <!-- Step 5 -->
                        <div class="step-tab d-flex flex-column align-items-center z-index-10 cursor-default opacity-50" data-step="5">
                            <div class="step-num bg-secondary text-muted">5</div>
                            <span class="text-muted">Docs</span>
                        </div>

                        <!-- Line 5-6 -->
                        <div class="step-line flex-grow-1 mx-2" style="height: 3px; background-color: rgba(0,0,0,0.1);" data-line="5"></div>

                        <!-- Step 6 -->
                        <div class="step-tab d-flex flex-column align-items-center z-index-10 cursor-default opacity-50" data-step="6">
                            <div class="step-num bg-secondary text-muted">6</div>
                            <span class="text-muted">Preview</span>
                        </div>
                    </div>
                </div>

                @php
                    $detail = $customer->customerDetail;
                    $names = explode(' ', $customer->name, 2);
                    $firstName = $names[0] ?? '';
                    $lastName = $names[1] ?? '';
                @endphp

                <form id="customerForm" action="{{ route('customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data" class="mt-4">
                    @csrf

                    <!-- Step 1: Referral & Status -->
                    <div class="step-content" id="step-1">
                        <div class="card bg-light text-dark border p-4 mb-4">
                            <h5 class="card-title text-dark mb-4">Referral & Status</h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="referral_code">Staff Referral Code</label>
                                    <input type="text" name="referral_code" id="referral_code" value="{{ $customer->referredBy->staffDetail->emp_code ?? '' }}" class="form-control bg-white text-dark" placeholder="Enter staff employee code (e.g. EMP-1)">
                                    <small class="text-muted">Optional. Enter the employee code of the referring staff member.</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="status">Account Status</label>
                                    <select name="status" id="status" class="form-control bg-white text-dark">
                                        <option value="1" {{ $customer->status === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $customer->status !== 'active' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-primary next-step px-4">
                                Next <i class="mdi mdi-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Personal Details -->
                    <div class="step-content d-none" id="step-2">
                        <div class="card bg-light text-dark border p-4 mb-4">
                            <h5 class="card-title text-dark mb-4">Personal Details</h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" required value="{{ $firstName }}" class="form-control bg-white text-dark" placeholder="Enter first name">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" value="{{ $lastName }}" class="form-control bg-white text-dark" placeholder="Enter last name">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Father's Name <span class="text-danger">*</span></label>
                                    <input type="text" name="father_name" required value="{{ $detail->father_name ?? '' }}" class="form-control bg-white text-dark" placeholder="Enter father's name">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Mother's Name</label>
                                    <input type="text" name="mother_name" value="{{ $detail->mother_name ?? '' }}" class="form-control bg-white text-dark" placeholder="Enter mother's name">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Nominee Name</label>
                                    <input type="text" name="nominee_name" value="{{ $detail->nominee_name ?? '' }}" class="form-control bg-white text-dark" placeholder="Enter nominee's name">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="dob" value="{{ $detail->dob ?? '' }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control bg-white text-dark">
                                        <option value="">Select Gender</option>
                                        <option value="Male" {{ ($detail->gender ?? '') === 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ ($detail->gender ?? '') === 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ ($detail->gender ?? '') === 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Marital Status</label>
                                    <select name="marital_status" class="form-control bg-white text-dark">
                                        <option value="">Select Status</option>
                                        <option value="Single" {{ ($detail->marital_status ?? '') === 'Single' ? 'selected' : '' }}>Single</option>
                                        <option value="Married" {{ ($detail->marital_status ?? '') === 'Married' ? 'selected' : '' }}>Married</option>
                                        <option value="Divorced" {{ ($detail->marital_status ?? '') === 'Divorced' ? 'selected' : '' }}>Divorced</option>
                                        <option value="Widowed" {{ ($detail->marital_status ?? '') === 'Widowed' ? 'selected' : '' }}>Widowed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary prev-step px-4">
                                <i class="mdi mdi-arrow-left mr-1"></i> Prev
                            </button>
                            <button type="button" class="btn btn-primary next-step px-4">
                                Next <i class="mdi mdi-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Contact Details -->
                    <div class="step-content d-none" id="step-3">
                        <div class="card bg-light text-dark border p-4 mb-4">
                            <h5 class="card-title text-dark mb-4">Contact Information</h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" required value="{{ $customer->email }}" class="form-control bg-white text-dark" placeholder="Enter email address">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" required value="{{ $customer->phone }}" class="form-control bg-white text-dark" placeholder="Enter phone number">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>WhatsApp Number <span class="text-danger">*</span></label>
                                    <input type="text" name="whatsapp_number" required value="{{ $customer->whatsapp_number }}" class="form-control bg-white text-dark" placeholder="Enter whatsapp number">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Alternate Number</label>
                                    <input type="text" name="alternate_number" value="{{ $detail->alternate_number ?? '' }}" class="form-control bg-white text-dark" placeholder="Enter alternate phone number">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Pincode <span class="text-danger">*</span></label>
                                    <input type="text" name="pincode" required value="{{ $detail->pincode ?? '' }}" class="form-control bg-white text-dark" placeholder="Enter pincode">
                                </div>
                                <div class="col-12 form-group">
                                    <label>Address <span class="text-danger">*</span></label>
                                    <textarea name="address" required rows="2" class="form-control bg-white text-dark" placeholder="Enter full address">{{ $detail->address ?? '' }}</textarea>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>City <span class="text-danger">*</span></label>
                                    <input type="text" name="city" required value="{{ $detail->city ?? '' }}" class="form-control bg-white text-dark" placeholder="City">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>State <span class="text-danger">*</span></label>
                                    <input type="text" name="state" required value="{{ $detail->state ?? '' }}" class="form-control bg-white text-dark" placeholder="State">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Country <span class="text-danger">*</span></label>
                                    <input type="text" name="country" required value="{{ $detail->country ?? '' }}" class="form-control bg-white text-dark" placeholder="Country">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary prev-step px-4">
                                <i class="mdi mdi-arrow-left mr-1"></i> Prev
                            </button>
                            <button type="button" class="btn btn-primary next-step px-4">
                                Next <i class="mdi mdi-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Occupation & Bank Details -->
                    <div class="step-content d-none" id="step-4">
                        <div class="card bg-light text-dark border p-4 mb-4">
                            <h5 class="card-title text-dark mb-4">Occupation & Bank Details</h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Occupation</label>
                                    <input type="text" name="occupation" value="{{ $detail->occupation ?? '' }}" class="form-control bg-white text-dark" placeholder="Occupation">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Annual Income</label>
                                    <input type="text" name="annual_income" value="{{ $detail->annual_income ?? '' }}" class="form-control bg-white text-dark" placeholder="Annual Income">
                                </div>
                                
                                <div class="col-12 my-2"><hr style="border-color: rgba(0,0,0,0.1);"></div>

                                <div class="col-md-6 form-group">
                                    <label>Bank Name</label>
                                    <input type="text" name="bank_name" value="{{ $detail->bank_name ?? '' }}" class="form-control bg-white text-dark" placeholder="Bank Name">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Account Number</label>
                                    <input type="text" name="account_number" value="{{ $detail->account_number ?? '' }}" class="form-control bg-white text-dark" placeholder="Account Number">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>IFSC Code</label>
                                    <input type="text" name="ifsc_code" value="{{ $detail->ifsc_code ?? '' }}" class="form-control bg-white text-dark" placeholder="IFSC Code">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Branch</label>
                                    <input type="text" name="branch" value="{{ $detail->branch ?? '' }}" class="form-control bg-white text-dark" placeholder="Branch Name">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>PAN Number</label>
                                    <input type="text" name="pan_number" value="{{ $detail->pan_number ?? '' }}" class="form-control bg-white text-dark" placeholder="PAN Card Number">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Aadhar Number</label>
                                    <input type="text" name="aadhar_number" value="{{ $detail->aadhar_number ?? '' }}" class="form-control bg-white text-dark" placeholder="Aadhar Card Number">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary prev-step px-4">
                                <i class="mdi mdi-arrow-left mr-1"></i> Prev
                            </button>
                            <button type="button" class="btn btn-primary next-step px-4">
                                Next <i class="mdi mdi-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 5: Docs -->
                    <div class="step-content d-none" id="step-5">
                        <div class="card bg-light text-dark border p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title text-dark mb-0">Customer KYC Documents</h5>
                                <button type="button" id="addDocRow" class="btn btn-success btn-sm">
                                    <i class="mdi mdi-plus mr-1"></i> Add More
                                </button>
                            </div>

                            @if($customer->customerDocuments->count() > 0)
                                <h6 class="text-dark mb-3">Existing Documents:</h6>
                                <div class="row mb-4">
                                    @foreach($customer->customerDocuments as $doc)
                                        <div class="col-md-6 mb-2" id="doc-{{ $doc->id }}">
                                            <div class="bg-white p-3 rounded d-flex justify-content-between align-items-center border">
                                                <div class="text-truncate">
                                                    <i class="mdi mdi-file text-info mr-2"></i>
                                                    <span class="small font-weight-bold text-dark">{{ $doc->document_name }}</span>
                                                </div>
                                                <div>
                                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-outline-primary btn-xs mr-1 py-1" style="padding: 0.2rem 0.4rem;">
                                                        <i class="mdi mdi-download"></i>
                                                    </a>
                                                    <button type="button" onclick="deleteExistingDoc({{ $doc->id }})" class="btn btn-outline-danger btn-xs py-1" style="padding: 0.2rem 0.4rem;">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div id="documentRows">
                                <div class="row mb-3 doc-row align-items-end">
                                    <div class="col-md-5 form-group">
                                        <label>Document Name</label>
                                        <input type="text" name="document_names[]" class="validate-doc-name form-control bg-white text-dark" placeholder="e.g. Photo, ID Card">
                                    </div>
                                    <div class="col-md-5 form-group">
                                        <label>File Upload</label>
                                        <input type="file" name="documents[]" class="validate-file form-control-file form-control bg-white text-dark" style="height: auto;">
                                    </div>
                                    <div class="col-md-2 form-group text-center">
                                        <button type="button" class="btn btn-danger remove-row">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary prev-step px-4">
                                <i class="mdi mdi-arrow-left mr-1"></i> Prev
                            </button>
                            <button type="button" class="btn btn-primary next-step px-4">
                                Preview <i class="mdi mdi-eye ml-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 6: Preview -->
                    <div class="step-content d-none" id="step-6">
                        <div class="card bg-light text-dark border p-4 mb-4">
                            <h5 class="card-title text-dark mb-4">Review Changes</h5>
                            <div id="previewContainer" class="bg-light rounded p-4 border text-dark">
                                <!-- Dynamic Preview Content -->
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary prev-step px-4">
                                <i class="mdi mdi-arrow-left mr-1"></i> Back
                            </button>
                            <button type="submit" id="submitBtn" class="btn btn-success px-5">
                                <i class="mdi mdi-check mr-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        let currentStep = 1;
        const totalSteps = 6;

        function updateStepUI() {
            $('.step-tab').each(function () {
                let step = $(this).data('step');
                let numCircle = $(this).find('.step-num');
                let labelText = $(this).find('span');

                numCircle.removeClass('step-icon-active bg-secondary text-muted');
                $(this).removeClass('opacity-50 opacity-100 cursor-pointer cursor-default');
                labelText.removeClass('text-dark text-muted font-weight-bold');

                if (step < currentStep) {
                    $(this).addClass('opacity-100 cursor-pointer');
                    numCircle.addClass('step-icon-active');
                    numCircle.html('✓');
                    labelText.addClass('text-dark font-weight-bold');
                } else if (step == currentStep) {
                    $(this).addClass('opacity-100 cursor-pointer');
                    numCircle.addClass('step-icon-active');
                    numCircle.html(step);
                    labelText.addClass('text-dark font-weight-bold');
                } else {
                    $(this).addClass('opacity-50 cursor-default');
                    numCircle.addClass('bg-secondary text-muted');
                    numCircle.html(step);
                    labelText.addClass('text-muted');
                }
            });

            // Update Progress Lines
            $('.step-line').each(function () {
                let lineNum = $(this).data('line');
                $(this).removeClass('step-line-active');
                if (lineNum < currentStep) {
                    $(this).addClass('step-line-active');
                }
            });

            $('.step-content').addClass('d-none');
            $(`#step-${currentStep}`).removeClass('d-none');

            if (currentStep === 6) {
                generatePreview();
            }
        }

        updateStepUI();

        // Initialize Validation
        let validator = $("#customerForm").validate({
            ignore: [],
            rules: {
                first_name: { required: true, minlength: 2 },
                last_name: { required: false },
                father_name: { required: true, minlength: 2 },
                email: { required: true, email: true },
                phone: { required: true },
                whatsapp_number: { required: true },
                pincode: { required: true },
                address: { required: true },
                city: { required: true },
                state: { required: true },
                country: { required: true }
            }
        });

        function validateFileElement(element) {
            let file = element.files ? element.files[0] : null;
            let parent = $(element).closest('.form-group');
            parent.find('.error-message').remove();
            $(element).removeClass('border-danger');

            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    $(element).addClass('border-danger');
                    parent.append('<p class="text-danger text-xs mt-1 error-message">File size must not exceed 2 MB.</p>');
                    return false;
                }
                let ext = file.name.split('.').pop().toLowerCase();
                if (!['jpg', 'jpeg', 'png', 'pdf'].includes(ext)) {
                    $(element).addClass('border-danger');
                    parent.append('<p class="text-danger text-xs mt-1 error-message">Only JPG, JPEG, PNG, and PDF are allowed.</p>');
                    return false;
                }
            }
            return true;
        }

        function validateDocNameElement(element) {
            let val = $(element).val().trim();
            let parent = $(element).closest('.form-group');
            parent.find('.error-message').remove();
            $(element).removeClass('border-danger');

            let fileInput = parent.closest('.doc-row').find('.validate-file');
            if (fileInput.val() && !val) {
                $(element).addClass('border-danger');
                parent.append('<p class="text-danger text-xs mt-1 error-message">Document name is required when file is uploaded.</p>');
                return false;
            }
            return true;
        }

        $(document).on('change', '.validate-file', function() {
            validateFileElement(this);
        });

        $(document).on('blur keyup', '.validate-doc-name', function() {
            validateDocNameElement(this);
        });

        function validateCurrentStep() {
            let isValid = true;
            let firstInvalid = null;

            $(`#step-${currentStep}`).find('input, select, textarea').not('.validate-file, .validate-doc-name').each(function () {
                if (!validator.element(this)) {
                    isValid = false;
                    if (!firstInvalid) firstInvalid = $(this);
                }
            });

            $(`#step-${currentStep}`).find('.validate-file').each(function() {
                if (!validateFileElement(this)) {
                    isValid = false;
                    if (!firstInvalid) firstInvalid = $(this);
                }
            });
            $(`#step-${currentStep}`).find('.validate-doc-name').each(function() {
                if (!validateDocNameElement(this)) {
                    isValid = false;
                    if (!firstInvalid) firstInvalid = $(this);
                }
            });

            if (!isValid && firstInvalid) {
                firstInvalid.focus();
            }
            return isValid;
        }

        $('.next-step').on('click', function () {
            if (validateCurrentStep()) {
                currentStep++;
                updateStepUI();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        $('.prev-step').on('click', function () {
            currentStep--;
            updateStepUI();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        $('.step-tab').on('click', function () {
            let targetStep = $(this).data('step');
            if (targetStep < currentStep) {
                currentStep = targetStep;
                updateStepUI();
            }
        });

        function generatePreview() {
            let name = $('input[name="first_name"]').val() + ' ' + $('input[name="last_name"]').val();
            let referral = $('input[name="referral_code"]').val() || 'None';
            let previewHtml = `
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h5 class="text-primary font-weight-bold mb-3">Personal Details</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><span class="text-muted">Full Name:</span> <strong>${name}</strong></li>
                            <li class="mb-2"><span class="text-muted">Father's Name:</span> <strong>${$('input[name="father_name"]').val()}</strong></li>
                            <li class="mb-2"><span class="text-muted">DOB:</span> <strong>${$('input[name="dob"]').val() || 'Not specified'}</strong></li>
                            <li class="mb-2"><span class="text-muted">Gender:</span> <strong>${$('select[name="gender"]').val() || 'Not specified'}</strong></li>
                            <li class="mb-2"><span class="text-muted">Referral:</span> <strong>${referral}</strong></li>
                        </ul>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h5 class="text-primary font-weight-bold mb-3">Contact Details</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><span class="text-muted">Email:</span> <strong>${$('input[name="email"]').val()}</strong></li>
                            <li class="mb-2"><span class="text-muted">Phone:</span> <strong>${$('input[name="phone"]').val()}</strong></li>
                            <li class="mb-2"><span class="text-muted">WhatsApp:</span> <strong>${$('input[name="whatsapp_number"]').val()}</strong></li>
                            <li class="mb-2"><span class="text-muted">Address:</span> <strong>${$('textarea[name="address"]').val()}</strong></li>
                            <li class="mb-2"><span class="text-muted">Location:</span> <strong>${$('input[name="city"]').val()}, ${$('input[name="state"]').val()}, ${$('input[name="country"]').val()} - ${$('input[name="pincode"]').val()}</strong></li>
                        </ul>
                    </div>
                </div>
            `;
            $('#previewContainer').html(previewHtml);
        }

        $('#addDocRow').on('click', function () {
            let row = `
                <div class="row mb-3 doc-row align-items-end">
                    <div class="col-md-5 form-group">
                        <label>Document Name</label>
                        <input type="text" name="document_names[]" class="validate-doc-name form-control bg-white text-dark" placeholder="Document Name">
                    </div>
                    <div class="col-md-5 form-group">
                        <label>File Upload</label>
                        <input type="file" name="documents[]" class="validate-file form-control-file form-control bg-white text-dark" style="height: auto;">
                    </div>
                    <div class="col-md-2 form-group text-center">
                        <button type="button" class="btn btn-danger remove-row">
                            <i class="mdi mdi-delete"></i>
                        </button>
                    </div>
                </div>
            `;
            $('#documentRows').append(row);
        });

        $(document).on('click', '.remove-row', function () {
            if ($('.doc-row').length > 1) {
                $(this).closest('.doc-row').remove();
            } else {
                $(this).closest('.doc-row').find('input').val('');
                $(this).closest('.doc-row').find('.error-message').remove();
                $(this).closest('.doc-row').find('input').removeClass('border-danger');
            }
        });

        $('#customerForm').on('submit', function (e) {
            e.preventDefault();
            let isValid = true;
            let firstInvalid = null;

            if (!$(this).valid()) {
                isValid = false;
            }

            $('.validate-file').each(function() {
                if (!validateFileElement(this)) {
                    isValid = false;
                    if (!firstInvalid) firstInvalid = $(this);
                }
            });
            $('.validate-doc-name').each(function() {
                if (!validateDocNameElement(this)) {
                    isValid = false;
                    if (!firstInvalid) firstInvalid = $(this);
                }
            });

            if (!isValid) {
                if (firstInvalid) firstInvalid.focus();
                return false;
            }

            // Append combined full name to the payload
            let name = $('input[name="first_name"]').val() + ' ' + $('input[name="last_name"]').val();
            let formData = new FormData(this);
            formData.append('name', name);

            let submitBtn = $('#submitBtn');
            submitBtn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Customer Updated',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    }).then(() => {
                        window.location.href = "{{ route('customers.index') }}";
                    });
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html('<i class="mdi mdi-check mr-1"></i> Save Changes');
                    let errors = xhr.responseJSON.errors;
                    let errorMsg = '';
                    if (errors) {
                        Object.keys(errors).forEach(key => {
                            errorMsg += errors[key][0] + '\n';
                        });
                    } else {
                        errorMsg = xhr.responseJSON.error || 'Something went wrong';
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

    function deleteExistingDoc(docId) {
        Swal.fire({
            title: 'Delete Document?',
            text: 'Are you sure you want to delete this document permanently?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff3ca6',
            cancelButtonColor: '#8392ab',
            confirmButtonText: 'Yes, delete!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/customers/delete-document') }}/${docId}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        Swal.fire('Deleted!', response.success, 'success');
                        $(`#doc-${docId}`).fadeOut(300, function() {
                            $(this).remove();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire('Error', 'Failed to delete document', 'error');
                    }
                });
            }
        });
    }
</script>
@endpush
