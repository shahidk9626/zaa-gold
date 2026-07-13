<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="text-center sidebar-brand-wrapper d-flex align-items-center">
    <a class="sidebar-brand brand-logo" href="{{ url('/') }}"><img src="{{ asset('assets/images/logo.svg') }}" alt="logo" /></a>
    <a class="sidebar-brand brand-logo-mini pl-4 pt-3" href="{{ url('/') }}"><img src="{{ asset('assets/images/logo-mini.svg') }}" alt="logo" /></a>
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
    @if(hasPermission('roles.view') || hasPermission('user-permissions.view') || hasPermission('staff.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#access-control" aria-expanded="false" aria-controls="access-control">
        <i class="mdi mdi-security menu-icon"></i>
        <span class="menu-title">Access Control</span>
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
            <a class="nav-link" href="{{ route('role-permissions.index') }}">Role Permissions</a>
          </li>
          @endif
          @if(hasPermission('user-permissions.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('user-permissions.index') }}">User Permissions</a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif

    @if(hasPermission('customer.view') || hasPermission('customer.create'))
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
            <a class="nav-link" href="{{ route('customers.index') }}">Customer List</a>
          </li>
          @endif
          @if(hasPermission('customer.create'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('customers.create') }}">Add Customer</a>
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
            <a class="nav-link" href="{{ route('staff.index') }}">Staff List</a>
          </li>
          @endif
          @if(hasPermission('staff.create'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('staff.create') }}">Add Staff</a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif

    @if(hasPermission('product.view') || hasPermission('gold-price.view') || hasPermission('inventory.view') || hasPermission('kyc.view') || hasPermission('emi-plan.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#masters-menu" aria-expanded="false" aria-controls="masters-menu">
        <i class="mdi mdi-database menu-icon"></i>
        <span class="menu-title">Masters</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="masters-menu">
        <ul class="nav flex-column sub-menu">
          @if(hasPermission('gold-price.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('gold-prices.index') }}">Gold Prices</a>
          </li>
          @endif
          @if(hasPermission('product.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('products.index') }}">Products</a>
          </li>
          @endif
          
          @if(hasPermission('inventory.view'))
          <!-- <li class="nav-item">
            <a class="nav-link" href="{{ route('inventory.index') }}">Inventory</a>
          </li> -->
          @endif
          @if(hasPermission('kyc.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('kyc.index') }}">KYC Review</a>
          </li>
          @endif
          @if(hasPermission('emi-plan.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('emi-plans.index') }}">EMI Plans</a>
          </li>
          @endif
        </ul>
      </div>
    </li>
    @endif
    @if(hasPermission('purchase-preview.view') || hasPermission('emi-calculator.view') || hasPermission('booking.view') || hasPermission('emi-schedule.view') || hasPermission('payment.view') || hasPermission('receipt.view') || hasPermission('invoice.view') || hasPermission('delivery.view'))
    <li class="nav-item">
      <a class="nav-link" data-toggle="collapse" href="#transactions-menu" aria-expanded="false" aria-controls="transactions-menu">
        <i class="mdi mdi-currency-usd menu-icon"></i>
        <span class="menu-title">Transactions</span>
        <i class="menu-arrow"></i>
      </a>
      <div class="collapse" id="transactions-menu">
        <ul class="nav flex-column sub-menu">
          @if(hasPermission('purchase-preview.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('purchase-preview.index') }}">Customer Plan purchase</a>
          </li>
          @endif
          @if(hasPermission('emi-calculator.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('emi-calculator.index') }}">EMI Calculator</a>
          </li>
          @endif
          @if(hasPermission('booking.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('bookings.index') }}">Bookings</a>
          </li>
          @endif
          @if(hasPermission('emi-schedule.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('emi-schedules.index') }}">EMI Schedule</a>
          </li>
          @endif
          @if(hasPermission('payment.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('payments.index') }}">EMI Payments</a>
          </li>
          @endif
          @if(hasPermission('receipt.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('receipts.index') }}">Receipts</a>
          </li>
          @endif
          @if(hasPermission('invoice.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('invoices.index') }}">GST Invoices</a>
          </li>
          @endif
          @if(hasPermission('delivery.view'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('deliveries.index') }}">Delivery</a>
          </li>
          @endif
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
