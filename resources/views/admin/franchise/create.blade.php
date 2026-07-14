@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-md-8 mx-auto grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <div>
                        <h4 class="card-title text-dark font-weight-bold">Log Franchise Enquiry</h4>
                        <p class="card-description text-muted">Register a new expansion partner lead or franchise interest request.</p>
                    </div>
                    <a href="{{ route('franchise.index') }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Back to Directory
                    </a>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="franchiseForm" action="{{ route('franchise.store') }}" method="POST" class="forms-sample">
                    @csrf
                    
                    <!-- Contact Details -->
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Partner Full Name</label>
                            <input type="text" name="full_name" class="form-control bg-white text-dark" placeholder="Enter full name" value="{{ old('full_name') }}" required>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control bg-white text-dark" placeholder="Enter 10-digit number" value="{{ old('mobile') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group mb-3">
                            <label class="font-weight-bold">Email Address</label>
                            <input type="email" name="email" class="form-control bg-white text-dark" placeholder="Enter email" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-md-4 form-group mb-3">
                            <label class="font-weight-bold">City</label>
                            <input type="text" name="city" class="form-control bg-white text-dark" placeholder="Enter city" value="{{ old('city') }}" required>
                        </div>
                        <div class="col-md-4 form-group mb-3">
                            <label class="font-weight-bold">State</label>
                            <input type="text" name="state" class="form-control bg-white text-dark" placeholder="Enter state" value="{{ old('state') }}" required>
                        </div>
                    </div>

                    <!-- Franchise Specs -->
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Investment Budget Bracket</label>
                            <select name="investment_budget" class="form-control bg-white text-dark" required>
                                <option value="">Select Budget</option>
                                <option value="₹10L - ₹25L" {{ old('investment_budget') === '₹10L - ₹25L' ? 'selected' : '' }}>₹10 Lakhs to ₹25 Lakhs</option>
                                <option value="₹25L - ₹50L" {{ old('investment_budget') === '₹25L - ₹50L' ? 'selected' : '' }}>₹25 Lakhs to ₹50 Lakhs</option>
                                <option value="₹50L - ₹1Cr" {{ old('investment_budget') === '₹50L - ₹1Cr' ? 'selected' : '' }}>₹50 Lakhs to ₹1 Crore</option>
                                <option value="Above ₹1Cr" {{ old('investment_budget') === 'Above ₹1Cr' ? 'selected' : '' }}>Above ₹1 Crore</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Current Business Type</label>
                            <input type="text" name="current_business" class="form-control bg-white text-dark" placeholder="e.g. Retail Jeweler" value="{{ old('current_business') }}">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Prior Business Experience</label>
                        <textarea name="business_experience" class="form-control bg-white text-dark" rows="2" placeholder="Briefly describe previous business ventures...">{{ old('business_experience') }}</textarea>
                    </div>

                    <!-- Follow-up & Assignee -->
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Assign Staff Member</label>
                            <select name="assigned_to" class="form-control bg-white text-dark">
                                <option value="">Leave Unassigned</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" {{ old('assigned_to') == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->name }} ({{ $staff->role->name ?? 'Staff' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">First Meeting / Follow-up Date</label>
                            <input type="date" name="followup_date" class="form-control bg-white text-dark" value="{{ old('followup_date') }}">
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Status</label>
                        <select name="status" class="form-control bg-white text-dark" required>
                            @foreach(['New', 'Contacted', 'Meeting Scheduled', 'Proposal Sent', 'Approved', 'Rejected', 'Closed'] as $st)
                                <option value="{{ $st }}" {{ old('status', 'New') === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Message from Partner</label>
                        <textarea name="message" class="form-control bg-white text-dark" rows="3" placeholder="Enter partnership query or message...">{{ old('message') }}</textarea>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold">Remarks / Internal Office Notes</label>
                        <textarea name="remarks" class="form-control bg-white text-dark" rows="2" placeholder="Office remarks...">{{ old('remarks') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2 px-4">Save Enquiry</button>
                    <a href="{{ route('franchise.index') }}" class="btn btn-light px-4">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $("#franchiseForm").validate({
            rules: {
                full_name: {
                    required: true,
                    lettersnspaces: true,
                    minlength: 3
                },
                mobile: {
                    required: true,
                    indianmobile: true
                },
                email: {
                    required: true,
                    email: true
                },
                city: "required",
                state: "required",
                investment_budget: "required",
                status: "required"
            }
        });
    });
</script>
@endpush
