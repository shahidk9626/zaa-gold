<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="font-weight-bold text-white mb-2">Reset Password</h4>
        <p class="text-muted">Enter a new password for your account</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="form-group mb-3">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" />
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-group mb-3">
            <label for="password">New Password</label>
            <input id="password" type="password" name="password" 
                   class="form-control @error('password') is-invalid @enderror" 
                   required autocomplete="new-password" 
                   placeholder="Enter a new strong password" />
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
                   placeholder="Confirm your new password" />
            @error('password_confirmation')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-block py-3 font-weight-bold" style="border-radius: 0.375rem; font-size: 1rem;">
            Reset Password
        </button>
    </form>
</x-guest-layout>
