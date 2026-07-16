<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Breeze Admin</title>
    <link rel="stylesheet" href="{{ asset('assets/vendors/mdi/css/materialdesignicons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/flag-icon-css/css/flag-icon.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/vendor.bundle.base.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/font-awesome/css/font-awesome.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.png') }}" />
  </head>
  <body>
    <div class="container-scroller">
      @include('layouts.partials.sidebar')
      <div class="container-fluid page-body-wrapper">
        <div id="theme-settings" class="settings-panel">
          <i class="settings-close mdi mdi-close"></i>
          <p class="settings-heading">SIDEBAR SKINS</p>
          <div class="sidebar-bg-options selected" id="sidebar-default-theme">
            <div class="img-ss rounded-circle bg-light border mr-3"></div> Default
          </div>
          <div class="sidebar-bg-options" id="sidebar-dark-theme">
            <div class="img-ss rounded-circle bg-dark border mr-3"></div> Dark
          </div>
          <p class="settings-heading mt-2">HEADER SKINS</p>
          <div class="color-tiles mx-0 px-4">
            <div class="tiles light"></div>
            <div class="tiles dark"></div>
          </div>
        </div>
        @include('layouts.partials.header')
        <div class="main-panel">
          <div class="content-wrapper pb-0">
            @yield('content')
          </div>
          @include('layouts.partials.footer')
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="{{ asset('assets/vendors/js/vendor.bundle.base.js') }}"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="{{ asset('assets/vendors/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/flot/jquery.flot.js') }}"></script>
    <script src="{{ asset('assets/vendors/flot/jquery.flot.resize.js') }}"></script>
    <script src="{{ asset('assets/vendors/flot/jquery.flot.categories.js') }}"></script>
    <script src="{{ asset('assets/vendors/flot/jquery.flot.fillbetween.js') }}"></script>
    <script src="{{ asset('assets/vendors/flot/jquery.flot.stack.js') }}"></script>
    <script src="{{ asset('assets/vendors/flot/jquery.flot.pie.js') }}"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="{{ asset('assets/js/off-canvas.js') }}"></script>
    <script src="{{ asset('assets/js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('assets/js/misc.js') }}"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
    <!-- End custom js for this page -->

    <!-- Access Control Libraries & Custom Validators -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>
    <script>
        $(document).ready(function() {
            if (typeof $.validator !== 'undefined') {
                // Name validation (only letters and spaces, min 3 chars)
                $.validator.addMethod("lettersnspaces", function(value, element) {
                    return this.optional(element) || /^[a-zA-Z\s]+$/.test(value);
                }, "Only alphabets and spaces are allowed");

                // Indian mobile validation (starts 6-9, exactly 10 digits)
                $.validator.addMethod("indianmobile", function(value, element) {
                    return this.optional(element) || /^[6-9]\d{9}$/.test(value);
                }, "Please enter a valid 10-digit Indian mobile number starting with 6-9");

                // Aadhaar validation (exactly 12 digits, numeric)
                $.validator.addMethod("aadhar", function(value, element) {
                    return this.optional(element) || /^\d{12}$/.test(value);
                }, "Aadhaar number must be exactly 12 digits");

                // PAN validation
                $.validator.addMethod("pan", function(value, element) {
                    return this.optional(element) || /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(value.toUpperCase());
                }, "Please enter a valid PAN card number (e.g. ABCDE1234F)");

                // Pincode validation (exactly 6 digits)
                $.validator.addMethod("pincode_custom", function(value, element) {
                    return this.optional(element) || /^\d{6}$/.test(value);
                }, "Pincode must be exactly 6 digits");

                // Date of birth validation (cannot be in the future)
                $.validator.addMethod("pastdate", function(value, element) {
                    if (!value) return true;
                    let inputDate = new Date(value);
                    let today = new Date();
                    inputDate.setHours(0,0,0,0);
                    today.setHours(0,0,0,0);
                    return inputDate <= today;
                }, "Date cannot be a future date");

                // File size validation (max 2MB)
                $.validator.addMethod("filesize", function(value, element, param) {
                    if (this.optional(element)) return true;
                    if (element.files && element.files.length > 0) {
                        for (let i = 0; i < element.files.length; i++) {
                            if (element.files[i].size > param) {
                                return false;
                            }
                        }
                    }
                    return true;
                }, "File size must not exceed 2 MB.");

                // File extension validation (jpg, jpeg, png, pdf)
                $.validator.addMethod("extension_custom", function(value, element, param) {
                    if (this.optional(element)) return true;
                    let ext = value.split('.').pop().toLowerCase();
                    let allowed = param.split('|');
                    return allowed.includes(ext);
                }, "Invalid file type. Only jpg, jpeg, png, pdf are allowed.");

                // Strong password validation
                $.validator.addMethod("strong_password", function(value, element) {
                    return this.optional(element) || /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(value);
                }, "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character");

                // Configure default validate options
                $.validator.setDefaults({
                    errorElement: 'p',
                    errorClass: 'text-danger text-xs mt-1 error-message',
                    highlight: function(element, errorClass, validClass) {
                        $(element).addClass('border-danger');
                    },
                    unhighlight: function(element, errorClass, validClass) {
                        $(element).removeClass('border-danger');
                    },
                    errorPlacement: function(error, element) {
                        let parent = element.closest('.form-group') || element.parent();
                        parent.find('.error-message').remove();
                        error.appendTo(parent);
                    }
                });
            }

            // Automatically transform all admin tables to DataTables
            $('table.table').each(function() {
                if (!this.id && !$.fn.DataTable.isDataTable(this) && !$(this).hasClass('no-datatable') && !$(this).parents('.no-datatable-parent').length) {
                    $(this).DataTable({
                        "paging": true,
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "responsive": true,
                        "language": {
                            "search": "",
                            "searchPlaceholder": "Quick Search..."
                        }
                    });
                }
            });
        });
    </script>
    <style>
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #212529 !important;
            font-size: 0.875rem;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ced4da;
            background-color: #ffffff;
            color: #212529;
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            outline: none;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #3f50f6 !important;
            color: white !important;
            border: 1px solid #3f50f6 !important;
        }
    </style>
    @stack('scripts')
  </body>
</html>
