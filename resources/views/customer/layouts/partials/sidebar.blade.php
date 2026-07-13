<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="text-center sidebar-brand-wrapper d-flex align-items-center">
    <a class="sidebar-brand brand-logo" href="{{ route('customer.dashboard') }}"><img src="{{ asset('assets/images/logo.svg') }}" alt="logo" /></a>
    <a class="sidebar-brand brand-logo-mini pl-4 pt-3" href="{{ route('customer.dashboard') }}"><img src="{{ asset('assets/images/logo-mini.svg') }}" alt="logo" /></a>
  </div>
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="{{ route('customer.profile.index') }}" class="nav-link">
        <div class="nav-profile-image">
          <img src="{{ asset('assets/images/faces/face1.jpg') }}" alt="profile" />
          <span class="login-status online"></span>
        </div>
        <div class="nav-profile-text d-flex flex-column pr-3">
          <span class="font-weight-medium mb-2">{{ Auth::user()->name }}</span>
          <span class="font-weight-normal text-muted" style="font-size: 0.75rem;">Customer</span>
        </div>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}" href="{{ route('customer.dashboard') }}">
        <i class="mdi mdi-home menu-icon"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#customer-plans-menu" aria-expanded="{{ request()->routeIs('customer.plans.*', 'customer.emi.*', 'customer.outstanding.*') ? 'true' : 'false' }}">
        <i class="mdi mdi-gold menu-icon"></i>
        <span class="menu-title">My Plans</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('customer.plans.*', 'customer.emi.*', 'customer.outstanding.*') ? 'show' : '' }}" id="customer-plans-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('customer.plans.index') }}">All Plans</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('customer.emi.history') }}">EMI History</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('customer.emi.repay') }}">Repay EMI</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('customer.outstanding.index') }}">Outstanding</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#customer-payments-menu" aria-expanded="{{ request()->routeIs('customer.payments.*', 'customer.certificates.*') ? 'true' : 'false' }}">
        <i class="mdi mdi-cash-multiple menu-icon"></i>
        <span class="menu-title">Payments</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse {{ request()->routeIs('customer.payments.*', 'customer.certificates.*') ? 'show' : '' }}" id="customer-payments-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item"><a class="nav-link" href="{{ route('customer.payments.index') }}">Payment History</a></li>
          <li class="nav-item"><a class="nav-link" href="{{ route('customer.certificates.index') }}">Receipts & Certificates</a></li>
        </ul>
      </div>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('customer.deliveries.*') ? 'active' : '' }}" href="{{ route('customer.deliveries.index') }}">
        <i class="mdi mdi-truck-delivery menu-icon"></i>
        <span class="menu-title">Delivery</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('customer.profile.*') ? 'active' : '' }}" href="{{ route('customer.profile.index') }}">
        <i class="mdi mdi-account menu-icon"></i>
        <span class="menu-title">Profile</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('customer.notifications.*') ? 'active' : '' }}" href="{{ route('customer.notifications.index') }}">
        <i class="mdi mdi-bell-outline menu-icon"></i>
        <span class="menu-title">Notifications</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link {{ request()->routeIs('customer.support.*') ? 'active' : '' }}" href="{{ route('customer.support.index') }}">
        <i class="mdi mdi-lifebuoy menu-icon"></i>
        <span class="menu-title">Support</span>
      </a>
    </li>

    <li class="nav-item sidebar-actions mt-3">
      <form id="customer-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
      <a class="nav-link text-danger" href="#" onclick="event.preventDefault(); document.getElementById('customer-logout-form').submit();">
        <i class="mdi mdi-logout menu-icon"></i>
        <span class="menu-title">Sign Out</span>
      </a>
    </li>
  </ul>
</nav>
