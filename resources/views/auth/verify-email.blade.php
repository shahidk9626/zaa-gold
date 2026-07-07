<x-guest-layout>
    <div class="mb-4 text-center">
        <h4 class="font-weight-bold text-white mb-2">Verify Your Email</h4>
        <p class="text-muted">Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            A new verification link has been sent to the email address you provided during registration.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mt-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary font-weight-bold px-4 py-2">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-light font-weight-bold px-4 py-2">
                Log Out
            </button>
        </form>
    </div>
</x-guest-layout>
