<div class="dashboard-empty-state">
    <div class="dashboard-empty-state__icon">
        <i data-lucide="{{ $icon ?? 'inbox' }}"></i>
    </div>
    <div class="dashboard-empty-state__content">
        <strong>{{ $title }}</strong>
        <p>{{ $message }}</p>
    </div>
</div>
