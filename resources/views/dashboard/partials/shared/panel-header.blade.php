<div class="card-header dashboard-panel-header">
    <div class="dashboard-panel-heading">
        <h3>{{ $title }}</h3>
        @if(! empty($subtitle))
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if(! empty($action_href) && ! empty($action_label))
        <a href="{{ $action_href }}" class="dashboard-panel-link">{{ $action_label }}</a>
    @endif
</div>
