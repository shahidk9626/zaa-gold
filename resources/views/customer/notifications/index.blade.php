<x-customer-layout title="Notifications">
    <div class="page-header flex-wrap d-none d-md-flex"><h3 class="mb-0">Notifications</h3></div>
    <div class="d-block d-md-none mb-3"><h5 class="font-weight-bold">Notifications</h5></div>

    @if($notifications->isEmpty())
        <div class="alert alert-info">No notifications yet.</div>
    @else
        @php
            $timelineItems = $notifications->map(fn($n) => [
                'title' => $n['title'],
                'description' => $n['message'],
                'date' => $n['date']->format('d M Y, h:i A'),
                'completed' => true,
            ])->toArray();
        @endphp
        <div class="d-none d-md-block">
            <div class="card">
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach($notifications as $notification)
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between">
                                <h6 class="font-weight-bold mb-1">{{ $notification['title'] }}</h6>
                                <small class="text-muted">{{ $notification['date']->diffForHumans() }}</small>
                            </div>
                            <p class="text-muted small mb-0">{{ $notification['message'] }}</p>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="d-block d-md-none">
            @include('customer.components.timeline', ['items' => $timelineItems])
        </div>
    @endif
</x-customer-layout>
