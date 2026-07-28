<x-customer-layout title="EMI History">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">EMI History</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">EMI History</h5></div>

    <div class="row mb-4">
        @foreach([
            ['label' => 'Paid EMI', 'value' => $paid_emi, 'color' => 'success'],
            ['label' => 'Pending EMI', 'value' => $pending_emi, 'color' => 'warning'],
            ['label' => 'Total Paid', 'value' => '₹' . number_format($total_paid, 0), 'color' => 'primary'],
            ['label' => 'Outstanding', 'value' => '₹' . number_format($outstanding, 0), 'color' => 'danger'],
        ] as $stat)
        <div class="col-6 col-md-3 grid-margin">
            <div class="card bg-{{ $stat['color'] }}">
                <div class="card-body text-center text-white py-3">
                    <p class="mb-0 small">{{ $stat['label'] }}</p>
                    <h4 class="mb-0">{{ $stat['value'] }}</h4>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-7 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">EMI Schedule</h5>
                    @if($schedule->isEmpty())
                        <p class="text-muted">No EMI schedules found.</p>
                    @else
                        @php
                            $groupedSchedule = $schedule->groupBy('booking_id');
                        @endphp
                        
                        {{-- Tab headers --}}
                        <ul class="nav nav-tabs border-0 bg-light rounded p-1 mb-3 flex-row flex-nowrap" id="emiBookingTabs" role="tablist" style="overflow-x: auto; white-space: nowrap;">
                            @foreach($groupedSchedule as $bookingId => $emis)
                                @php
                                    $booking = $emis->first()->booking;
                                @endphp
                                <li class="nav-item" role="presentation" style="margin-bottom:0; flex-shrink: 0;">
                                    <a class="nav-link {{ $loop->first ? 'active' : '' }} font-weight-bold" 
                                       id="tab-booking-{{ $bookingId }}" 
                                       data-toggle="tab" 
                                       href="#pane-booking-{{ $bookingId }}" 
                                       role="tab" 
                                       aria-controls="pane-booking-{{ $bookingId }}" 
                                       aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                        #{{ $booking->booking_number }} ({{ number_format($booking->gold_weight, 1) }}g)
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Tab contents --}}
                        <div class="tab-content" id="emiBookingTabsContent" style="padding-top:0; border: none; background: transparent; box-shadow: none;">
                            @foreach($groupedSchedule as $bookingId => $emis)
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                                     id="pane-booking-{{ $bookingId }}" 
                                     role="tabpanel" 
                                     aria-labelledby="tab-booking-{{ $bookingId }}">
                                    @foreach($emis as $emi)
                                        @include('customer.components.emi-card', ['schedule' => $emi, 'showPayButton' => false])
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-5 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Payments</h5>
                    @forelse($recent_payments as $payment)
                        @include('customer.components.payment-card', ['payment' => $payment])
                    @empty
                        <p class="text-muted">No recent payments.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-customer-layout>
