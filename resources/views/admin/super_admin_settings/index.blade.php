@extends('layouts.app')

@section('content')
<div class="row text-dark">
    <!-- Header -->
    <div class="col-12 mb-4">
        <div class="card bg-white border shadow-sm p-4">
            <h4 class="card-title text-dark font-weight-bold mb-1">Super Admin Account</h4>
            <p class="card-description text-muted mb-0">Update the login email address and password of your Super Admin account.</p>
        </div>
    </div>

    <!-- Alert Block -->
    @if(session('success'))
        <div class="col-12 mb-4">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close text-dark" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif
    @if(session('info'))
        <div class="col-12 mb-4">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="mdi mdi-information mr-1"></i> {{ session('info') }}
                <button type="button" class="close text-dark" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif

    <!-- Change Email -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Change Email Address</h5>

            <div class="form-group">
                <label class="text-dark font-weight-bold">Current Email</label>
                <input type="text" class="form-control bg-light text-dark" value="{{ $user->email }}" readonly>
            </div>

            <form method="POST" action="{{ route('admin.super-admin-settings.email') }}" autocomplete="off">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="email" class="text-dark font-weight-bold">New Email Address <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control bg-white text-dark {{ $errors->updateEmail->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" required maxlength="255">
                    @if($errors->updateEmail->has('email'))
                        <p class="text-danger text-xs mt-1">{{ $errors->updateEmail->first('email') }}</p>
                    @endif
                </div>

                <div class="form-group">
                    <label for="email_current_password" class="text-dark font-weight-bold">Current Password <span class="text-danger">*</span></label>
                    <input type="password" id="email_current_password" name="current_password" class="form-control bg-white text-dark {{ $errors->updateEmail->has('current_password') ? 'is-invalid' : '' }}" required autocomplete="current-password">
                    @if($errors->updateEmail->has('current_password'))
                        <p class="text-danger text-xs mt-1">{{ $errors->updateEmail->first('current_password') }}</p>
                    @endif
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4">Update Email</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-md-6 mb-4">
        <div class="card bg-white border shadow-sm p-4 h-100">
            <h5 class="text-dark font-weight-bold mb-3 border-bottom pb-2">Change Password</h5>

            <form method="POST" action="{{ route('admin.super-admin-settings.password') }}" autocomplete="off">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="password_current_password" class="text-dark font-weight-bold">Current Password <span class="text-danger">*</span></label>
                    <input type="password" id="password_current_password" name="current_password" class="form-control bg-white text-dark {{ $errors->updatePassword->has('current_password') ? 'is-invalid' : '' }}" required autocomplete="current-password">
                    @if($errors->updatePassword->has('current_password'))
                        <p class="text-danger text-xs mt-1">{{ $errors->updatePassword->first('current_password') }}</p>
                    @endif
                </div>

                <div class="form-group">
                    <label for="password" class="text-dark font-weight-bold">New Password <span class="text-danger">*</span></label>
                    <input type="password" id="password" name="password" class="form-control bg-white text-dark {{ $errors->updatePassword->has('password') ? 'is-invalid' : '' }}" required minlength="8" autocomplete="new-password">
                    @if($errors->updatePassword->has('password'))
                        <p class="text-danger text-xs mt-1">{{ $errors->updatePassword->first('password') }}</p>
                    @endif
                    <small class="text-muted">Minimum 8 characters. Must differ from the current password.</small>
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="text-dark font-weight-bold">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control bg-white text-dark" required minlength="8" autocomplete="new-password">
                </div>

                <p class="text-muted small mb-3">After a password change, your other signed-in sessions are logged out. This session stays signed in.</p>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
