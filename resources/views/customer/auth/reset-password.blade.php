<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="font-weight-bold text-white mb-2">Reset Password</h4>
        <p class="text-muted">Enter a new secure password for <strong>{{ $email }}</strong></p>
    </div>

    <!-- Alert / Messages -->
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form method="POST" action="{{ route('customer.reset-password') }}">
        @csrf

        <!-- Password -->
        <div class="form-group mb-3">
            <label for="password">New Password</label>
            <input id="password" type="password" name="password" 
                   class="form-control mt-1 @error('password') is-invalid @enderror" 
                   required autocomplete="new-password" autofocus
                   placeholder="Enter new password" />
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="form-group mb-4">
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" 
                   class="form-control mt-1" 
                   required autocomplete="new-password"
                   placeholder="Confirm new password" />
        </div>

        <button type="submit" class="btn btn-primary btn-block py-3 font-weight-bold" style="border-radius: 0.375rem; font-size: 1rem;">
            Reset Password
        </button>
    </form>

    <div class="text-center mt-4">
        <a href="{{ route('login') }}" class="text-link text-small">Back to Login</a>
    </div>
</x-guest-layout>
