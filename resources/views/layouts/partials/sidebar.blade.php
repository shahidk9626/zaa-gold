<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="text-center sidebar-brand-wrapper d-flex align-items-center">
    <a class="sidebar-brand brand-logo" href="{{ url('/') }}"><img src="{{ asset('assets/images/logo.png') }}" alt="logo" style="height: 50px; object-fit: contain;" /></a>
    <a class="sidebar-brand brand-logo-mini pl-4 pt-3" href="{{ url('/') }}"><img src="{{ asset('assets/images/logo.png') }}" alt="logo" style="height: 40px; object-fit: contain;" /></a>
  </div>
  <ul class="nav">
    <li class="nav-item nav-profile">
      <a href="#" class="nav-link">
        <div class="nav-profile-image">
          <img src="{{ asset('assets/images/faces/face1.jpg') }}" alt="profile" />
          <span class="login-status online"></span>
          <!--change to offline or busy as needed-->
        </div>
        <div class="nav-profile-text d-flex flex-column pr-3">
          <span class="font-weight-medium mb-2">{{ Auth::user()->name }}</span>
          <span class="font-weight-normal text-muted" style="font-size: 0.75rem;">{{ Auth::user()->role->name ?? 'User' }}</span>
        </div>
        <span class="badge badge-danger text-white ml-3 rounded">3</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="{{ url('/') }}">
        <i class="mdi mdi-home menu-icon"></i>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>

    @if(hasPermission('customer.view') || hasPermission('customer.create') || hasPermission('cancellations.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#customer-menu" aria-expanded="false" aria-controls="customer-menu">
        <i class="mdi mdi-account-multiple menu-icon"></i>
        <span class="menu-title">Customer</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="customer-menu">
        <ul class="nav flex-column sub-menu">
          @if(hasPermission('customer.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('customers.index') }}">Customer list</a>
          </li>
          @endif
          @if(hasPermission('customer.create'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('customers.create') }}">Add customer</a>
          </li>
          @endif
          @if(hasPermission('cancellations.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.cancellations.index') }}">Cancellation requests</a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif

    @if(hasPermission('booking.view') || hasPermission('purchase-preview.view') || hasPermission('emi-schedule.view') || hasPermission('payment.view') || hasPermission('cash-collection.view') || hasPermission('payment.dashboard') || hasPermission('payment.logs') || hasPermission('payment.links') || hasPermission('payment.failed') || hasPermission('receipt.view') || hasPermission('delivery.view') || hasPermission('emi-calculator.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#transactions-menu" aria-expanded="false" aria-controls="transactions-menu">
        <i class="mdi mdi-currency-usd menu-icon"></i>
        <span class="menu-title">Transactions</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="transactions-menu">
        <ul class="nav flex-column sub-menu">
          @if(hasPermission('booking.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('bookings.index') }}">Bookings</a>
          </li>
          @endif
          @if(hasPermission('purchase-preview.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('purchase-preview.index') }}">Customer plan purchase</a>
          </li>
          @endif
          @if(hasPermission('emi-schedule.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('emi-schedules.index') }}">EMI schedule</a>
          </li>
          @endif
          @if(hasPermission('payment.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('payments.index') }}">EMI payments</a>
          </li>
          @endif
          @if(hasPermission('cash-collection.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.cash-collections.index') }}">Cash collection request</a>
          </li>
          @endif
          @if(hasPermission('payment.dashboard'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('payments.dashboard') }}">Payment dashboard</a>
          </li>
          @endif
          @if(hasPermission('payment.logs'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('payment-logs.index') }}">Payment logs</a>
          </li>
          @endif
          @if(hasPermission('payment.links'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('payment-links.index') }}">Payment links</a>
          </li>
          @endif
          @if(hasPermission('payment.failed'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('payments.failed') }}">Failed payments</a>
          </li>
          @endif
          @if(hasPermission('receipt.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('receipts.index') }}">Receipts</a>
          </li>
          @endif
          @if(hasPermission('delivery.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('deliveries.index') }}">Delivery</a>
          </li>
          @endif
          @if(hasPermission('emi-calculator.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('emi-calculator.index') }}">EMI calculator</a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif

    @if(hasPermission('product.view') || hasPermission('gold-price.view') || hasPermission('kyc.view') || hasPermission('emi-plan.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#masters-menu" aria-expanded="false" aria-controls="masters-menu">
        <i class="mdi mdi-database menu-icon"></i>
        <span class="menu-title">Gold plan</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="masters-menu">
        <ul class="nav flex-column sub-menu">
          @if(hasPermission('gold-price.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('gold-prices.index') }}">Gold prices</a>
          </li>
          @endif
          @if(hasPermission('kyc.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('kyc.index') }}">KYC review</a>
          </li>
          @endif
          @if(hasPermission('emi-plan.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('emi-plans.index') }}">EMI plans</a>
          </li>
          @endif
          @if(hasPermission('product.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('products.index') }}">Products</a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif

    @if(hasPermission('roles.view') || hasPermission('user-permissions.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#access-control" aria-expanded="false" aria-controls="access-control">
        <i class="mdi mdi-security menu-icon"></i>
        <span class="menu-title">Access control</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="access-control">
        <ul class="nav flex-column sub-menu">
          @if(hasPermission('roles.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('roles.index') }}">Roles</a>
          </li>
          @endif
          @if(hasPermission('roles.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('role-permissions.index') }}">Roles permission</a>
          </li>
          @endif
          @if(hasPermission('user-permissions.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('user-permissions.index') }}">User permission</a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif

    @if(hasPermission('staff.view') || hasPermission('staff.create'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#staff-menu" aria-expanded="false" aria-controls="staff-menu">
        <i class="mdi mdi-account-card-details menu-icon"></i>
        <span class="menu-title">Staff</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="staff-menu">
        <ul class="nav flex-column sub-menu">
          @if(hasPermission('staff.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('staff.index') }}">Staff list</a>
          </li>
          @endif
          @if(hasPermission('staff.create'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('staff.create') }}">Add staff</a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif

    @if(hasPermission('referral.view') || hasPermission('sell-old-gold.view') || hasPermission('franchise.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#crm-menu" aria-expanded="false" aria-controls="crm-menu">
        <i class="mdi mdi-briefcase-outline menu-icon"></i>
        <span class="menu-title">CRM</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="crm-menu">
        <ul class="nav flex-column sub-menu">
          @if(hasPermission('referral.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('referrals.index') }}">Referrals</a>
          </li>
          @endif
          @if(hasPermission('sell-old-gold.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('sell-old-gold.index') }}">Sell old gold</a>
          </li>
          @endif
          @if(hasPermission('franchise.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('franchise.index') }}">Franchise</a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif

    @if(hasPermission('offers.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#marketing-menu" aria-expanded="false" aria-controls="marketing-menu">
        <i class="mdi mdi-bullhorn menu-icon"></i>
        <span class="menu-title">Marketing</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="marketing-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="{{ route('offers.index') }}">Offers & Discounts</a>
          </li>
        </ul>
      </div>
    </li>
    @endif

    @if(hasPermission('report.view'))
    <li class="nav-item">
      <a class="nav-link" href="{{ route('reports.dashboard') }}">
        <i class="mdi mdi-chart-bar menu-icon"></i>
        <span class="menu-title">Reports</span>
      </a>
    </li>
    @endif

    @if(hasPermission('audit.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#system-menu" aria-expanded="false" aria-controls="system-menu">
        <i class="mdi mdi-shield-search menu-icon"></i>
        <span class="menu-title">System</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="system-menu">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="{{ route('audit-trail.index') }}">Audit trail</a>
          </li>
        </ul>
      </div>
    </li>
    @endif
    <!-- <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
        <i class="mdi mdi-crosshairs-gps menu-icon"></i>
        <span class="menu-title">Basic UI Elements</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="ui-basic">
        <ul class="nav flex-column sub-menu">
          <li class="nav-item">
            <a class="nav-link" href="#">Buttons</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Dropdowns</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Typography</a>
          </li>
        </ul>
      </div>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#">
        <i class="mdi mdi-contacts menu-icon"></i>
        <span class="menu-title">Icons</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#">
        <i class="mdi mdi-format-list-bulleted menu-icon"></i>
        <span class="menu-title">Forms</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#">
        <i class="mdi mdi-chart-bar menu-icon"></i>
        <span class="menu-title">Charts</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#">
        <i class="mdi mdi-table-large menu-icon"></i>
        <span class="menu-title">Tables</span>
      </a>
    </li>
    <li class="nav-item">
      <span class="nav-link" href="#">
        <span class="menu-title">Docs</span>
      </span>
    </li> -->
    <!-- <li class="nav-item">
      <a class="nav-link" href="https://www.bootstrapdash.com/demo/breeze-free/documentation/documentation.html">
        <i class="mdi mdi-file-document-box menu-icon"></i>
        <span class="menu-title">Documentation</span>
      </a>
    </li>
    <li class="nav-item sidebar-actions">
      <div class="nav-link">
        <div class="mt-4">
          <div class="border-none">
            <p class="text-black">Notification</p>
          </div>
          <ul class="mt-4 pl-0">
            <li>Sign Out</li>
          </ul>
        </div>
      </div>
    </li> -->
  </ul>
</nav>
