<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="font-weight-bold text-white mb-2">Confirm Password</h4>
        <p class="text-muted">This is a secure area of the application. Please confirm your password before continuing.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div class="form-group mb-4">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" 
                   class="form-control @error('password') is-invalid @enderror" 
                   required autocomplete="current-password" 
                   placeholder="Enter your password" />
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-block py-3 font-weight-bold" style="border-radius: 0.375rem; font-size: 1rem;">
            Confirm Password
        </button>
    </form>
</x-guest-layout>
