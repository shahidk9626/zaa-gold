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
        border: 2px solid rgba(255,255,255,0.1);
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
                        <h4 class="card-title text-dark">Edit Staff: <span class="text-primary font-weight-bold">{{ $staff->full_name }}</span></h4>
                        <p class="card-description text-muted">Modify employee credentials, personal information, and overrides</p>
                    </div>
                    <a href="{{ route('staff.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left mr-1"></i> Back to List
                    </a>
                </div>

                <!-- Step Navigation -->
                <div class="position-relative mb-5 mt-4">
                    <div class="d-flex justify-content-between align-items-center w-100 px-2">
                        <!-- Step 1 -->
                        <div class="step-tab d-flex flex-column align-items-center z-index-10 cursor-pointer" data-step="1">
                            <div class="step-num bg-secondary text-muted">1</div>
                            <span class="text-muted">Role</span>
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
                            <span class="text-muted">Employment</span>
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

                <form id="staffForm" action="{{ route('staff.update', $staff->slug) }}" method="POST" enctype="multipart/form-data" class="mt-4">
                    @csrf

                    <!-- Step 1: Role Selection & Overrides -->
                    <div class="step-content" id="step-1">
                        <div class="card bg-light text-dark border p-4 mb-4">
                            <h5 class="card-title text-dark mb-4">Set User Role</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 form-group">
                                    <label for="role_id">Select Role <span class="text-danger">*</span></label>
                                    <select name="role_id" id="role_id" required class="form-control bg-white text-dark">
                                        <option value="">Choose a role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="status">Account Status</label>
                                    <select name="status" id="status" class="form-control bg-white text-dark">
                                        <option value="1" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $user->status !== 'active' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <h5 class="card-title text-dark mb-3">User Specific Permissions (Overrides)</h5>
                            <div class="table-responsive border rounded">
                                <table class="table table-bordered table-hover text-dark mb-0">
                                    <thead class="bg-light text-dark">
                                        <tr>
                                            <th>Module</th>
                                            <th class="text-center">View</th>
                                            <th class="text-center">Create</th>
                                            <th class="text-center">Edit</th>
                                            <th class="text-center">Delete</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($modules as $module)
                                            <tr>
                                                <td class="font-weight-bold align-middle">{{ $module->name }}</td>
                                                @foreach (['view', 'create', 'edit', 'delete', 'status'] as $action)
                                                    @php
                                                        $permission = $module->permissions->where('slug', $module->slug . '.' . $action)->first();
                                                        $currentOverride = $permission ? ($userPermissions[$permission->id] ?? null) : null;
                                                    @endphp
                                                    <td class="text-center align-middle">
                                                        @if ($permission)
                                                            <select name="user_permissions[{{ $permission->id }}]" class="form-control form-control-sm bg-white text-dark d-inline-block w-auto">
                                                                <option value="" {{ $currentOverride === null ? 'selected' : '' }}>Inherit</option>
                                                                <option value="1" {{ $currentOverride === 1 ? 'selected' : '' }} class="text-success font-weight-bold">Allow</option>
                                                                <option value="0" {{ $currentOverride === 0 ? 'selected' : '' }} class="text-danger font-weight-bold">Deny</option>
                                                            </select>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
                                    <input type="text" name="first_name" required value="{{ $staff->first_name }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" value="{{ $staff->last_name }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Father's Name <span class="text-danger">*</span></label>
                                    <input type="text" name="father_name" required value="{{ $staff->father_name }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Mother's Name</label>
                                    <input type="text" name="mother_name" value="{{ $staff->mother_name }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Nominee Name</label>
                                    <input type="text" name="nominee_name" value="{{ $staff->nominee_name }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="dob" value="{{ $staff->dob }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control bg-white text-dark">
                                        <option value="">Select Gender</option>
                                        <option value="Male" {{ $staff->gender == 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ $staff->gender == 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ $staff->gender == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Marital Status</label>
                                    <select name="marital_status" class="form-control bg-white text-dark">
                                        <option value="">Select Status</option>
                                        <option value="Single" {{ $staff->marital_status == 'Single' ? 'selected' : '' }}>Single</option>
                                        <option value="Married" {{ $staff->marital_status == 'Married' ? 'selected' : '' }}>Married</option>
                                        <option value="Divorced" {{ $staff->marital_status == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                                        <option value="Widowed" {{ $staff->marital_status == 'Widowed' ? 'selected' : '' }}>Widowed</option>
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
                                    <label>Email Address</label>
                                    <input type="email" name="email" value="{{ $user->email }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" required value="{{ $user->phone }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Alternate Phone</label>
                                    <input type="text" name="alternate_phone" value="{{ $staff->alternate_phone }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Pincode <span class="text-danger">*</span></label>
                                    <input type="text" name="pincode" required value="{{ $staff->pincode }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-12 form-group">
                                    <label>Address <span class="text-danger">*</span></label>
                                    <textarea name="address" required rows="2" class="form-control bg-white text-dark">{{ $staff->address }}</textarea>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>City <span class="text-danger">*</span></label>
                                    <input type="text" name="city" required value="{{ $staff->city }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>State <span class="text-danger">*</span></label>
                                    <input type="text" name="state" required value="{{ $staff->state }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Country <span class="text-danger">*</span></label>
                                    <input type="text" name="country" required value="{{ $staff->country }}" class="form-control bg-white text-dark">
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

                    <!-- Step 4: Employment Details -->
                    <div class="step-content d-none" id="step-4">
                        <div class="card bg-light text-dark border p-4 mb-4">
                            <h5 class="card-title text-dark mb-4">Employment & Bank Details</h5>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Department</label>
                                    <input type="text" name="department" value="{{ $staff->department }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Designation</label>
                                    <input type="text" name="designation" value="{{ $staff->designation }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Joining Date <span class="text-danger">*</span></label>
                                    <input type="date" name="joining_date" required value="{{ $staff->joining_date }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Salary</label>
                                    <input type="number" step="0.01" name="salary" value="{{ $staff->salary }}" class="form-control bg-white text-dark">
                                </div>

                                <div class="col-12 my-2"><hr style="border-color: rgba(0,0,0,0.1);"></div>

                                <div class="col-md-6 form-group">
                                    <label>Bank Name</label>
                                    <input type="text" name="bank_name" value="{{ $staff->bank_name }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Account Number</label>
                                    <input type="text" name="account_number" value="{{ $staff->account_number }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>IFSC Code</label>
                                    <input type="text" name="ifsc_code" value="{{ $staff->ifsc_code }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>PAN Number</label>
                                    <input type="text" name="pan_number" value="{{ $staff->pan_number }}" class="form-control bg-white text-dark">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Aadhar Number</label>
                                    <input type="text" name="aadhar_number" value="{{ $staff->aadhar_number }}" class="form-control bg-white text-dark">
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
                                <h5 class="card-title text-dark mb-0">Documents</h5>
                                <button type="button" id="addDocRow" class="btn btn-success btn-sm">
                                    <i class="mdi mdi-plus mr-1"></i> Add Document
                                </button>
                            </div>

                            @if($staff->documents->count() > 0)
                                <h6 class="text-dark mb-3">Existing Uploads:</h6>
                                <div class="row mb-4">
                                    @foreach($staff->documents as $doc)
                                        <div class="col-md-6 mb-2" id="doc-{{ $doc->id }}">
                                            <div class="bg-light p-3 rounded d-flex justify-content-between align-items-center border">
                                                <div class="text-truncate">
                                                    <i class="mdi mdi-file text-info mr-2"></i>
                                                    <span class="small font-weight-bold">{{ $doc->document_name }}</span>
                                                </div>
                                                <button type="button" onclick="deleteDocument({{ $doc->id }})" class="btn btn-outline-danger btn-xs py-1">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <h6 class="text-dark mb-3">Upload New Documents:</h6>
                            <div id="documentRows"></div>
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
                            <h5 class="card-title text-dark mb-4">Review Information</h5>
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
                labelText.removeClass('text-white text-muted');

                if (step < currentStep) {
                    $(this).addClass('opacity-100 cursor-pointer');
                    numCircle.addClass('step-icon-active');
                    numCircle.html('✓');
                    labelText.addClass('text-white');
                } else if (step == currentStep) {
                    $(this).addClass('opacity-100 cursor-pointer');
                    numCircle.addClass('step-icon-active');
                    numCircle.html(step);
                    labelText.addClass('text-white');
                } else {
                    $(this).addClass('opacity-50 cursor-default');
                    numCircle.addClass('bg-secondary text-muted');
                    numCircle.html(step);
                    labelText.addClass('text-muted');
                }
            });

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

        // Initialize Validator
        let validator = $("#staffForm").validate({
            ignore: [],
            rules: {
                role_id: { required: true },
                first_name: { required: true, lettersnspaces: true, minlength: 3 },
                last_name: { lettersnspaces: true, minlength: 1 },
                father_name: { required: true, lettersnspaces: true, minlength: 3 },
                mother_name: { lettersnspaces: true, minlength: 3 },
                dob: { pastdate: true },
                email: { email: true },
                phone: { required: true, indianmobile: true },
                pincode: { required: true, pincode_custom: true },
                address: { required: true },
                city: { required: true, lettersnspaces: true },
                state: { required: true, lettersnspaces: true },
                country: { required: true, lettersnspaces: true },
                joining_date: { required: true, pastdate: true },
                salary: { number: true, min: 0 }
            },
            messages: {
                first_name: { required: "First Name is required" },
                father_name: { required: "Father Name is required" },
                phone: { required: "Phone number is required" },
                pincode: { required: "Pincode is required" },
                address: { required: "Address is required" },
                city: { required: "City is required" },
                state: { required: "State is required" },
                country: { required: "Country is required" },
                joining_date: { required: "Joining date is required" }
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
                    parent.append('<p class="text-danger text-xs mt-1 error-message">Invalid file type. Only jpg, jpeg, png, pdf are allowed.</p>');
                    return false;
                }
            } else if ($(element).prop('required')) {
                $(element).addClass('border-danger');
                parent.append('<p class="text-danger text-xs mt-1 error-message">This field is required.</p>');
                return false;
            }
            return true;
        }

        function validateDocNameElement(element) {
            let val = $(element).val().trim();
            let parent = $(element).closest('.form-group');
            parent.find('.error-message').remove();
            $(element).removeClass('border-danger');

            if (!val && $(element).prop('required')) {
                $(element).addClass('border-danger');
                parent.append('<p class="text-danger text-xs mt-1 error-message">This field is required.</p>');
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
            let roleName = $('select[name="role_id"] option:selected').text();
            let previewHtml = `
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h5 class="text-primary font-weight-bold mb-3">Role & Personal</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><span class="text-muted">Role:</span> <strong>${roleName}</strong></li>
                            <li class="mb-2"><span class="text-muted">Name:</span> <strong>${$('input[name="first_name"]').val()} ${$('input[name="last_name"]').val()}</strong></li>
                            <li class="mb-2"><span class="text-muted">Father's Name:</span> <strong>${$('input[name="father_name"]').val()}</strong></li>
                            <li class="mb-2"><span class="text-muted">DOB:</span> <strong>${$('input[name="dob"]').val() || 'Not provided'}</strong></li>
                        </ul>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h5 class="text-primary font-weight-bold mb-3">Contact Details</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><span class="text-muted">Phone:</span> <strong>${$('input[name="phone"]').val()}</strong></li>
                            <li class="mb-2"><span class="text-muted">Email:</span> <strong>${$('input[name="email"]').val() || 'N/A'}</strong></li>
                            <li class="mb-2"><span class="text-muted">Address:</span> <strong>${$('textarea[name="address"]').val()}</strong></li>
                            <li class="mb-2"><span class="text-muted">Location:</span> <strong>${$('input[name="city"]').val()}, ${$('input[name="state"]').val()}, ${$('input[name="country"]').val()}</strong></li>
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
            $(this).closest('.doc-row').remove();
        });

        window.deleteDocument = function (id) {
            Swal.fire({
                title: 'Delete Document',
                text: 'Are you sure you want to delete this document?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff3ca6',
                cancelButtonColor: '#8392ab',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('staff/delete-document') }}/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            Swal.fire('Deleted!', response.success, 'success');
                            $(`#doc-${id}`).remove();
                        }
                    });
                }
            });
        };

        $('#staffForm').on('submit', function (e) {
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

            let formData = new FormData(this);
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
                        title: 'Success!',
                        text: response.success,
                        confirmButtonColor: '#3f50f6'
                    }).then(() => {
                        window.location.href = "{{ route('staff.index') }}";
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
                        title: 'Error',
                        text: errorMsg,
                        confirmButtonColor: '#ff3ca6'
                    });
                }
            });
        });
    });
</script>
@endpush
