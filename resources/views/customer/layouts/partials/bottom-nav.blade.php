<nav class="customer-bottom-nav">
    <a href="{{ route('customer.dashboard') }}" class="{{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
        <i class="mdi mdi-home"></i>
        <span>Dashboard</span>
    </a>
    <a href="{{ route('customer.plans.index') }}" class="{{ request()->routeIs('customer.plans.*') ? 'active' : '' }}">
        <i class="mdi mdi-gold"></i>
        <span>Plans</span>
    </a>
    <a href="{{ route('customer.payments.index') }}" class="{{ request()->routeIs('customer.payments.*', 'customer.emi.*', 'customer.certificates.*') ? 'active' : '' }}">
        <i class="mdi mdi-cash-multiple"></i>
        <span>Payments</span>
    </a>
    <a href="{{ route('customer.deliveries.index') }}" class="{{ request()->routeIs('customer.deliveries.*') ? 'active' : '' }}">
        <i class="mdi mdi-truck-delivery"></i>
        <span>Delivery</span>
    </a>
    <a href="{{ route('customer.profile.index') }}" class="{{ request()->routeIs('customer.profile.*') ? 'active' : '' }}">
        <i class="mdi mdi-account"></i>
        <span>Profile</span>
    </a>
</nav>
