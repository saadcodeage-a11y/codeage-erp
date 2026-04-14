@php
    $percentage = max(0, min(100, (float) ($percentage ?? 0)));
    $color = $color ?? '#ff5b2e';
@endphp

<div class="rb-ring-card">
    <div class="rb-ring" style="--rb-ring-value: {{ $percentage }}; --rb-ring-color: {{ $color }};">
        <div class="rb-ring__inner">
            <strong>{{ $value }}</strong>
            <span>{{ $label }}</span>
        </div>
    </div>
    @if(! empty($meta))
        <p class="rb-ring-card__meta">{{ $meta }}</p>
    @endif
</div>
