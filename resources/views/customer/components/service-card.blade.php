@props(['icon', 'label', 'route', 'color' => 'primary'])

<a href="{{ $route }}" class="service-tile">
    <i class="mdi {{ $icon }} text-{{ $color }}"></i>
    <span>{{ $label }}</span>
</a>
