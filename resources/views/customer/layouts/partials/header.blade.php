<nav class="navbar col-lg-12 col-12 p-lg-0 fixed-top d-flex flex-row default-layout-navbar">
  <div class="navbar-menu-wrapper d-flex align-items-stretch justify-content-between">
    <a class="navbar-brand brand-logo-mini align-self-center d-lg-none" href="{{ route('customer.dashboard') }}"><img src="{{ asset('assets/images/logo-mini.svg') }}" alt="logo" /></a>
    <button class="navbar-toggler navbar-toggler align-self-center mr-2" type="button" data-toggle="minimize">
      <i class="mdi mdi-menu"></i>
    </button>
    <ul class="navbar-nav">
      <li class="nav-item dropdown">
        <a class="nav-link count-indicator dropdown-toggle" href="{{ route('customer.notifications.index') }}">
          <i class="mdi mdi-bell-outline"></i>
        </a>
      </li>
    </ul>
    <ul class="navbar-nav navbar-nav-right ml-lg-auto">
      @php $goldData = app(\App\Services\CustomerService::class)->getGoldPriceWithTrend(); @endphp
      @if($goldData['price'])
      <li class="nav-item d-none d-xl-flex border-0 mr-3">
        <span class="nav-link text-muted">
          <i class="mdi mdi-gold mr-1 text-warning"></i>
          22K: ₹{{ number_format($goldData['price']->price_22k, 0) }}/g
          &nbsp;|&nbsp;
          24K: ₹{{ number_format($goldData['price']->price_24k, 0) }}/g
        </span>
      </li>
      @endif
      <li class="nav-item nav-profile dropdown border-0">
        <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-toggle="dropdown">
          <img class="nav-profile-img mr-2" alt="" src="{{ asset('assets/images/faces/face1.jpg') }}" />
          <span class="profile-name">{{ Auth::check() ? Auth::user()->name : 'Guest' }}</span>
        </a>
        <div class="dropdown-menu navbar-dropdown w-100" aria-labelledby="profileDropdown">
          @if(Auth::check())
            <a class="dropdown-item" href="{{ route('customer.profile.index') }}">
              <i class="mdi mdi-account mr-2 text-primary"></i> My Profile
            </a>
            <a class="dropdown-item" href="{{ route('customer.notifications.index') }}">
              <i class="mdi mdi-bell mr-2 text-warning"></i> Notifications
            </a>
            <form id="logout-form-header" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();">
              <i class="mdi mdi-logout mr-2 text-danger"></i> Signout
            </a>
          @else
            <a class="dropdown-item" href="{{ route('login') }}">
              <i class="mdi mdi-login mr-2 text-success"></i> Login
            </a>
          @endif
        </div>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
      <span class="mdi mdi-menu"></span>
    </button>
  </div>
</nav>
