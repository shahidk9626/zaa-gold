<div class="customer-mobile-header d-flex align-items-center justify-content-between">
    <button class="btn btn-link p-0 text-dark" type="button" data-toggle="offcanvas">
        <i class="mdi mdi-menu" style="font-size: 1.5rem;"></i>
    </button>
    <div class="text-center flex-grow-1">
        <p class="mb-0 text-muted small">{{ Auth::check() ? 'Welcome back' : 'Welcome to ZAA Gold' }}</p>
        <h6 class="mb-0 font-weight-bold">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</h6>
    </div>
    <div class="d-flex align-items-center">
        <a href="{{ route('customer.notifications.index') }}" class="btn btn-link p-1 text-dark mr-1">
            <i class="mdi mdi-bell-outline" style="font-size: 1.3rem;"></i>
        </a>
        <a href="{{ route('customer.profile.index') }}" class="btn btn-link p-0">
            <img src="{{ asset('assets/images/faces/face1.jpg') }}" alt="" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;" />
        </a>
    </div>
</div>
