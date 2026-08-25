<x-customer-layout title="Customer Support">
    <style>
        .support-card-bg {
            background: #ffffff;
            border: 1px solid #f2e3d3;
            border-radius: 16px;
        }
        .support-info-box {
            background: #ffffff;
            border: 1px solid #f2e3d3;
            border-radius: 12px;
            height: 100%;
            transition: all 0.3s ease;
        }
        .support-info-box:hover {
            box-shadow: 0 4px 12px rgba(184, 134, 11, 0.12);
        }
        .icon-circle-gold {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background-color: #fdf6ea;
            color: #b8860b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 12px auto;
        }
        .icon-circle-ticket {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #fdf6ea;
            color: #b8860b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        .icon-circle-check {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #198754;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        .text-gold {
            color: #b8860b !important;
        }
        .btn-gold {
            background-color: #b8860b !important;
            border-color: #b8860b !important;
            color: #ffffff !important;
            border-radius: 8px;
        }
        .btn-gold:hover {
            background-color: #a07409 !important;
            border-color: #a07409 !important;
            color: #ffffff !important;
        }
        .support-ticket-box {
            background: #fdfaf6;
            border: 1px solid #f3e6d8;
            border-radius: 14px;
        }
        .support-reply-box {
            background: #ffffff;
            border: 1px solid #e1e7e0;
            border-radius: 14px;
        }
    </style>

    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9 grid-margin">
            <div class="card support-card-bg shadow-sm p-3 p-md-4">
                <div class="card-body">
                    {{-- Logo & Header Section --}}
                    <div class="text-center mb-4">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="AurOnGold Logo" style="height: 65px; object-fit: contain;" class="mb-2">
                        <h5 class="font-weight-bold mb-0 text-dark">Auron Gold</h5>
                        <p class="small text-muted mb-1 font-weight-bold">Pvt Ltd</p>
                        <p class="small text-gold font-weight-bold tracking-wide mb-3">— SMART WAY TO BUY GOLD —</p>
                        
                        <h3 class="font-weight-bold text-dark mt-3 mb-2">How can we help you?</h3>
                        <p class="text-muted">Our support team is here to assist you with your gold plans, <strong class="text-gold">EMAP</strong> and other any queries.</p>
                    </div>

                    {{-- Top 3 Contact Info Cards --}}
                    <div class="row text-center mb-4">
                        <!-- Call Us -->
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="support-info-box p-4 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon-circle-gold">
                                    <i class="mdi mdi-phone"></i>
                                </div>
                                <h5 class="font-weight-bold text-dark mb-1">Call Us</h5>
                                <a href="tel:7337616333" class="h5 font-weight-bold text-gold mb-1 text-decoration-none">7337616333</a>
                                <p class="text-muted small mb-0">Any Queries?</p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="support-info-box p-4 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon-circle-gold">
                                    <i class="mdi mdi-email-outline"></i>
                                </div>
                                <h5 class="font-weight-bold text-dark mb-1">Email</h5>
                                <a href="mailto:support@aurongold.in" class="font-weight-bold text-gold text-decoration-none mb-0">support@aurongold.in</a>
                            </div>
                        </div>

                        <!-- Hours -->
                        <div class="col-md-4">
                            <div class="support-info-box p-4 d-flex flex-column justify-content-center align-items-center">
                                <div class="icon-circle-gold">
                                    <i class="mdi mdi-clock-outline"></i>
                                </div>
                                <h5 class="font-weight-bold text-dark mb-2">Hours</h5>
                                <p class="text-dark small mb-2">Monday - Saturday<br>10:00 AM to 6:00 PM</p>
                                <p class="small mb-0"><strong class="text-gold">Sunday & Public Holidays</strong><br><span class="text-dark font-weight-medium">Closed</span></p>
                            </div>
                        </div>
                    </div>



                    {{-- Replied from Team Section --}}
                    <div class="support-reply-box p-4 mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-circle-check mr-3">
                                <i class="mdi mdi-check"></i>
                            </div>
                            <div>
                                <h5 class="font-weight-bold text-success mb-0">Replied from Team</h5>
                                <p class="text-muted small mb-0">Our team has replied to your ticket.</p>
                            </div>
                        </div>

                        <hr class="my-3" style="border-color: #e2e8f0;">

                        <h6 class="font-weight-bold text-dark mb-2">Support Team Response</h6>
                        <div class="bg-white p-3 p-md-4 rounded border text-dark" style="font-size: 0.95rem; line-height: 1.6; border-color: #e2e8f0 !important;">
                            <p class="mb-2">Dear Customer,</p>
                            <p class="mb-2">Thank you for reaching out to us.</p>
                            <p class="mb-2">We have received your query and our team is working on it.</p>
                            <p class="mb-4">We will get back to you shortly.</p>
                            <p class="mb-0 font-weight-bold">Thanks & Regards,<br><span class="text-muted font-weight-normal">Auron Gold Support Team</span></p>
                        </div>
                    </div>

                    {{-- Footer Banner --}}
                    <div class="bg-light p-3 text-center rounded border" style="border-color: #f2e3d3 !important;">
                        <p class="mb-0 text-dark small font-weight-medium">
                            <i class="mdi mdi-help-circle-outline text-gold mr-1" style="font-size: 1.2rem; vertical-align: middle;"></i>
                            Still need help? Call us at <a href="tel:7337616333" class="text-gold font-weight-bold text-decoration-none">7337616333</a> or email at <a href="mailto:support@aurongold.in" class="text-gold font-weight-bold text-decoration-none">support@aurongold.in</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            $('#supportTicketForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let btn = $('#submitTicketBtn');
                btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Submitting...');

                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        btn.prop('disabled', false).html('<i class="mdi mdi-send mr-1"></i> Raise Ticket');
                        Swal.fire({
                            icon: 'success',
                            title: 'Ticket Submitted',
                            text: 'Your support ticket has been submitted. Our team will get back to you shortly.',
                            confirmButtonColor: '#b8860b'
                        });
                        form.find('textarea').val('');
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html('<i class="mdi mdi-send mr-1"></i> Raise Ticket');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to submit ticket. Please try again.',
                            confirmButtonColor: '#ff3ca6'
                        });
                    }
                });
            });
        });
    </script>
    @endpush
</x-customer-layout>
