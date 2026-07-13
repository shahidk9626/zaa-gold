<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>{{ $title ?? 'ZAA Gold' }} — Customer Portal</title>
    <link rel="stylesheet" href="{{ asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/flag-icon-css/css/flag-icon.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/vendor.bundle.base.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/font-awesome/css/font-awesome.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/customer-portal.css') }}" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />
    @stack('styles')
</head>
<body class="customer-portal">
    <div class="container-scroller">
        @include('customer.layouts.partials.sidebar')

        <div class="container-fluid page-body-wrapper">
            {{-- Desktop Header --}}
            <div class="d-none d-md-block">
                @include('customer.layouts.partials.header')
            </div>

            {{-- Mobile Header --}}
            <div class="d-block d-md-none">
                @include('customer.layouts.partials.mobile-header')
            </div>

            <div class="main-panel">
                <div class="content-wrapper pb-0">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle mr-1"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle mr-1"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    @endif

                    {{ $slot }}
                </div>

                <div class="d-none d-md-block">
                    @include('customer.layouts.partials.footer')
                </div>
            </div>
        </div>
    </div>

  {{-- Mobile Bottom Navigation --}}
  <div class="d-block d-md-none">
      @include('customer.layouts.partials.bottom-nav')
  </div>

    <script src="{{ asset('assets/vendors/js/vendor.bundle.base.js') }}"></script>
    <script src="{{ asset('assets/js/off-canvas.js') }}"></script>
    <script src="{{ asset('assets/js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('assets/js/misc.js') }}"></script>
    @stack('scripts')
</body>
</html>
