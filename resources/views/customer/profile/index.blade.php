<x-customer-layout title="Profile">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">My Profile</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">Profile</h5></div>

    <div class="row">
        <div class="col-md-8 grid-margin">
            <form action="{{ route('customer.profile.update') }}" method="POST">
                @csrf
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="text-muted small">Full Name</label>
                                <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="text-muted small">Email</label>
                                <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>WhatsApp</label>
                                <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $user->whatsapp_number) }}">
                            </div>
                        </div>
                    </div>
                </div>

                @if($user->customerDetail)
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Nominee & Address</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Nominee Name</label>
                                <input type="text" name="nominee_name" class="form-control" value="{{ old('nominee_name', $user->customerDetail->nominee_name) }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Alternate Number</label>
                                <input type="text" name="alternate_number" class="form-control" value="{{ old('alternate_number', $user->customerDetail->alternate_number) }}">
                            </div>
                            <div class="col-12 form-group">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="2">{{ old('address', $user->customerDetail->address) }}</textarea>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>City</label>
                                <input type="text" name="city" class="form-control" value="{{ old('city', $user->customerDetail->city) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>State</label>
                                <input type="text" name="state" class="form-control" value="{{ old('state', $user->customerDetail->state) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Pincode</label>
                                <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $user->customerDetail->pincode) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">KYC <span class="badge badge-secondary">Read Only</span></h5>
                        <div class="row">
                            <div class="col-md-6"><p><strong>PAN:</strong> {{ $user->customerDetail->pan_number ?? '—' }}</p></div>
                            <div class="col-md-6"><p><strong>Aadhaar:</strong> {{ $user->customerDetail->aadhar_number ? '****' . substr($user->customerDetail->aadhar_number, -4) : '—' }}</p></div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Bank Details <span class="badge badge-secondary">Read Only</span></h5>
                        <p><strong>Bank:</strong> {{ $user->customerDetail->bank_name ?? '—' }}</p>
                        <p><strong>Account:</strong> {{ $user->customerDetail->account_number ? '****' . substr($user->customerDetail->account_number, -4) : '—' }}</p>
                        <p><strong>IFSC:</strong> {{ $user->customerDetail->ifsc_code ?? '—' }}</p>
                    </div>
                </div>

                @if($user->customerDetail->documents->count())
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Documents</h5>
                        <ul class="list-group list-group-flush">
                            @foreach($user->customerDetail->documents as $doc)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>{{ $doc->document_name ?? $doc->type ?? 'Document' }}</span>
                                <span class="badge badge-success">Uploaded</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
                @endif

                <button type="submit" class="btn btn-primary btn-mobile-lg">Save Changes</button>
            </form>
        </div>
    </div>
</x-customer-layout>
