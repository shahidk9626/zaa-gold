<x-customer-layout title="Repay EMI">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">Repay EMI</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">Upcoming EMI</h5></div>

    @if($upcomingEmis->isEmpty())
        <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> All EMIs are up to date. No pending payments.</div>
    @else
        <div class="card">
            <div class="card-body">
                @php
                    $groupedUpcoming = $upcomingEmis->groupBy('booking_id');
                @endphp
                
                {{-- Tab headers --}}
                <ul class="nav nav-tabs border-0 bg-light rounded p-1 mb-3 flex-row flex-nowrap" id="repayBookingTabs" role="tablist" style="overflow-x: auto; white-space: nowrap;">
                    @foreach($groupedUpcoming as $bookingId => $emis)
                        @php
                            $booking = $emis->first()->booking;
                        @endphp
                        <li class="nav-item" role="presentation" style="margin-bottom:0; flex-shrink: 0;">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }} font-weight-bold" 
                               id="tab-repay-{{ $bookingId }}" 
                               data-toggle="tab" 
                               href="#pane-repay-{{ $bookingId }}" 
                               role="tab" 
                               aria-controls="pane-repay-{{ $bookingId }}" 
                               aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                #{{ $booking->booking_number }} ({{ number_format($booking->gold_weight, 1) }}g)
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Tab contents --}}
                <div class="tab-content" id="repayBookingTabsContent" style="padding-top:0; border: none; background: transparent; box-shadow: none;">
                    @foreach($groupedUpcoming as $bookingId => $emis)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                             id="pane-repay-{{ $bookingId }}" 
                             role="tabpanel" 
                             aria-labelledby="tab-repay-{{ $bookingId }}">
                            @foreach($emis as $schedule)
                                @include('customer.components.emi-card', ['schedule' => $schedule])
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</x-customer-layout>
