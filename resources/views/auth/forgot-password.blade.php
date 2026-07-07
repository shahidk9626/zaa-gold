<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="font-weight-bold text-white mb-2">Forgot Password?</h4>
        <p class="text-muted">No problem. Enter your email and we'll send you a password reset link.</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div class="form-group mb-4">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   value="{{ old('email') }}" required autofocus 
                   placeholder="name@example.com" />
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-block py-3 font-weight-bold mb-4" style="border-radius: 0.375rem; font-size: 1rem;">
            Send Password Reset Link
        </button>

        <!-- Back to Login -->
        <p class="text-center text-muted mb-0" style="font-size: 0.9rem;">
            Remember your password? 
            <a href="{{ route('login') }}" class="text-link font-weight-bold">Back to Login</a>
        </p>
    </form>
</x-guest-layout>
