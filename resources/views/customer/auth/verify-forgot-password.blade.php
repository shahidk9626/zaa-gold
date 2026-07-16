<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="font-weight-bold text-white mb-2">Reset Password Verification</h4>
        <p class="text-muted">Enter the OTP sent to <strong>{{ $email }}</strong> to reset your password</p>
    </div>

    <!-- Alert / Messages -->
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form method="POST" action="{{ route('customer.verify-forgot-password') }}">
        @csrf

        <div id="otp-input-group" class="form-group mb-4">
            <label for="otp">Enter 6-Digit OTP</label>
            <input id="otp" type="text" name="otp" 
                   class="form-control text-center font-weight-bold @error('otp') is-invalid @enderror" 
                   required autofocus autocomplete="off" 
                   placeholder="000000" maxlength="6" 
                   style="font-size: 1.5rem; letter-spacing: 4px;" />
            @error('otp')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
            <div class="text-center mt-2">
                <small class="text-warning font-weight-bold" id="expiry-timer"></small>
            </div>
        </div>

        <button type="submit" id="verify-btn" class="btn btn-primary btn-block py-3 font-weight-bold mb-3" style="border-radius: 0.375rem; font-size: 1rem;">
            Verify OTP
        </button>
    </form>

    <form method="POST" action="{{ route('customer.resend-forgot-password-otp') }}" class="text-center">
        @csrf
        <p class="text-muted" style="font-size: 0.9rem;">
            Didn't receive the code? 
            <button type="submit" id="resend-btn" class="btn btn-link text-link font-weight-bold p-0 align-baseline" style="text-decoration: none;" disabled>
                Resend OTP
            </button>
            <span id="resend-timer" class="text-muted ml-1" style="font-size: 0.85rem;"></span>
        </p>
    </form>

    <div class="text-center mt-4">
        <a href="{{ route('password.request') }}" class="text-link text-small">Back to Forgot Password</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const expiresAt = new Date("{{ $expiresAt }}");
            const resendAfter = new Date("{{ $resendAfter }}");

            function updateTimers() {
                const now = new Date();
                
                // 1. Expiry Countdown
                const expiryDiff = expiresAt - now;
                if (expiryDiff <= 0) {
                    document.getElementById('expiry-timer').innerText = "OTP Expired. Please request a new one.";
                    document.getElementById('otp').disabled = true;
                    document.getElementById('verify-btn').disabled = true;
                } else {
                    const min = Math.floor(expiryDiff / 60000);
                    const sec = Math.floor((expiryDiff % 60000) / 1000);
                    document.getElementById('expiry-timer').innerText = `OTP expires in ${min}:${sec < 10 ? '0' : ''}${sec}`;
                }

                // 2. Resend Countdown
                const resendDiff = resendAfter - now;
                if (resendDiff <= 0) {
                    document.getElementById('resend-btn').disabled = false;
                    document.getElementById('resend-timer').innerText = "";
                } else {
                    document.getElementById('resend-btn').disabled = true;
                    const sec = Math.floor(resendDiff / 1000);
                    document.getElementById('resend-timer').innerText = `(${sec}s)`;
                }
            }

            // Run immediately and then every second
            updateTimers();
            setInterval(updateTimers, 1000);
        });
    </script>
</x-guest-layout>
