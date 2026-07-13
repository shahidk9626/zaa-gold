@props(['items', 'type' => 'default'])

<div class="timeline-mobile">
    @foreach($items as $item)
    <div class="timeline-item">
        <div class="timeline-dot {{ $item['completed'] ?? false ? 'completed' : ($item['pending'] ?? false ? 'pending' : '') }}"></div>
        <div class="card mobile-card">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between">
                    <h6 class="font-weight-bold mb-1">{{ $item['title'] }}</h6>
                    @if(isset($item['date']))
                    <small class="text-muted">{{ $item['date'] }}</small>
                    @endif
                </div>
                @if(isset($item['description']))
                <p class="text-muted small mb-0">{{ $item['description'] }}</p>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
