<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="font-weight-bold text-white mb-2">Create an Account</h4>
        <p class="text-muted">Fill in the details below to register on Zaa Gold</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="form-group mb-3">
            <label for="name">Full Name</label>
            <input id="name" type="text" name="name" 
                   class="form-control @error('name') is-invalid @enderror" 
                   value="{{ old('name') }}" required autofocus autocomplete="name" 
                   placeholder="John Doe" />
            @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="form-group mb-3">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   value="{{ old('email') }}" required autocomplete="username" 
                   placeholder="name@example.com" />
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-group mb-3">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" 
                   class="form-control @error('password') is-invalid @enderror" 
                   required autocomplete="new-password" 
                   placeholder="Create a strong password" />
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
                   class="form-control @error('password_confirmation') is-invalid @enderror" 
                   required autocomplete="new-password" 
                   placeholder="Confirm your password" />
            @error('password_confirmation')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-block py-3 font-weight-bold" style="border-radius: 0.375rem; font-size: 1rem;">
            Register Account
        </button>

        <!-- Social Register Separator -->
        <div class="divider">Or register with</div>

        <!-- Social Logins (UI Only) -->
        <div class="row mb-4">
            <div class="col-4">
                <a href="javascript:void(0)" class="btn btn-social btn-block">
                    <i class="bi bi-google"></i> Google
                </a>
            </div>
            <div class="col-4">
                <a href="javascript:void(0)" class="btn btn-social btn-block">
                    <i class="bi bi-facebook"></i> Facebook
                </a>
            </div>
            <div class="col-4">
                <a href="javascript:void(0)" class="btn btn-social btn-block">
                    <i class="bi bi-github"></i> GitHub
                </a>
            </div>
        </div>

        <!-- Login Link -->
        <p class="text-center text-muted mb-0" style="font-size: 0.9rem;">
            Already have an account? 
            <a href="{{ route('login') }}" class="text-link font-weight-bold">Login</a>
        </p>
    </form>
</x-guest-layout>
