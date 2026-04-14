<div class="rb-panel__head">
    <div>
        <span class="rb-panel__eyebrow">{{ $eyebrow ?? 'Overview' }}</span>
        <h3>{{ $title }}</h3>
        @if(! empty($subtitle))
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if(! empty($badge))
        <span class="rb-panel__badge">{{ $badge }}</span>
    @endif
</div>
