<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="font-weight-bold text-white mb-2">Sign In to Zaa Gold</h4>
        <p class="text-muted">Enter your credentials below to access your account</p>
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

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="form-group mb-3">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   value="{{ old('email') }}" required autofocus autocomplete="username" 
                   placeholder="name@example.com" />
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="mb-0">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-link text-small" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input id="password" type="password" name="password" 
                   class="form-control mt-1 @error('password') is-invalid @enderror" 
                   required autocomplete="current-password" 
                   placeholder="Enter your password" />
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="form-group mb-4">
            <div class="form-check text-muted">
                <label class="form-check-label" style="font-size: 0.875rem; color: #cbd5e1; cursor: pointer;">
                    <input id="remember_me" type="checkbox" name="remember" class="form-check-input"> Remember me
                    <i class="input-helper"></i>
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-block py-3 font-weight-bold" style="border-radius: 0.375rem; font-size: 1rem;">
            Log In
        </button>

        <!-- Social Login Separator -->
        <div class="divider">Or continue with</div>

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

        <!-- Register Link -->
        <p class="text-center text-muted mb-0" style="font-size: 0.9rem;">
            Don't have an account? 
            <a href="{{ route('register') }}" class="text-link font-weight-bold">Register</a>
        </p>
    </form>
</x-guest-layout>
