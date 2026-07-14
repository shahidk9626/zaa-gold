@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <div class="col-md-8 mx-auto grid-margin stretch-card">
        <div class="card bg-white border shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <div>
                        <h4 class="card-title text-dark font-weight-bold">Edit Old Gold Enquiry</h4>
                        <p class="card-description text-muted">Update contact info, evaluation values, and schedules.</p>
                    </div>
                    <a href="{{ route('sell-old-gold.show', $enquiry->id) }}" class="btn btn-secondary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Cancel & Back
                    </a>
                </div>

                <form id="oldGoldEditForm" action="{{ route('sell-old-gold.update', $enquiry->id) }}" method="POST" class="forms-sample">
                    @csrf
                    
                    <!-- Customer Details -->
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Customer Name</label>
                            <input type="text" name="customer_name" class="form-control bg-white text-dark" value="{{ old('customer_name', $enquiry->customer_name) }}" required>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control bg-white text-dark" value="{{ old('mobile', $enquiry->mobile) }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Email Address</label>
                            <input type="email" name="email" class="form-control bg-white text-dark" value="{{ old('email', $enquiry->email) }}">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">City</label>
                            <input type="text" name="city" class="form-control bg-white text-dark" value="{{ old('city', $enquiry->city) }}">
                        </div>
                    </div>

                    <!-- Gold Specifications -->
                    <div class="row">
                        <div class="col-md-4 form-group mb-3">
                            <label class="font-weight-bold">Gold Type</label>
                            <select name="gold_type" class="form-control bg-white text-dark" required>
                                <option value="18K" {{ old('gold_type', $enquiry->gold_type) === '18K' ? 'selected' : '' }}>18K Gold</option>
                                <option value="22K" {{ old('gold_type', $enquiry->gold_type) === '22K' ? 'selected' : '' }}>22K Gold</option>
                                <option value="24K" {{ old('gold_type', $enquiry->gold_type) === '24K' ? 'selected' : '' }}>24K Gold</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group mb-3">
                            <label class="font-weight-bold">Est. Weight (grams)</label>
                            <input type="number" step="0.01" name="estimated_weight" class="form-control bg-white text-dark" value="{{ old('estimated_weight', $enquiry->estimated_weight) }}" required>
                        </div>
                        <div class="col-md-4 form-group mb-3">
                            <label class="font-weight-bold">Est. Value (₹)</label>
                            <input type="number" step="0.01" name="estimated_value" class="form-control bg-white text-dark" value="{{ old('estimated_value', $enquiry->estimated_value) }}">
                        </div>
                    </div>

                    <!-- Follow-up & Assignee -->
                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Assign Staff Member</label>
                            <select name="assigned_to" class="form-control bg-white text-dark">
                                <option value="">Leave Unassigned</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" {{ old('assigned_to', $enquiry->assigned_to) == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->name }} ({{ $staff->role->name ?? 'Staff' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold">Next Follow-up Date</label>
                            <input type="date" name="followup_date" class="form-control bg-white text-dark" value="{{ old('followup_date', $enquiry->followup_date ? $enquiry->followup_date->format('Y-m-d') : '') }}">
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Status</label>
                        <select name="status" class="form-control bg-white text-dark" required>
                            @foreach(['New', 'Contacted', 'Inspection Scheduled', 'Quoted', 'Accepted', 'Rejected', 'Closed'] as $st)
                                <option value="{{ $st }}" {{ old('status', $enquiry->status) === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold">Remarks / Enquiry Description</label>
                        <textarea name="remarks" class="form-control bg-white text-dark" rows="3">{{ old('remarks', $enquiry->remarks) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2 px-4">Update Enquiry</button>
                    <a href="{{ route('sell-old-gold.show', $enquiry->id) }}" class="btn btn-light px-4">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $("#oldGoldEditForm").validate({
            rules: {
                customer_name: {
                    required: true,
                    lettersnspaces: true,
                    minlength: 3
                },
                mobile: {
                    required: true,
                    indianmobile: true
                },
                email: {
                    email: true
                },
                estimated_weight: {
                    required: true,
                    number: true,
                    min: 0.1
                },
                estimated_value: {
                    number: true,
                    min: 0
                },
                status: "required"
            }
        });
    });
</script>
@endpush
