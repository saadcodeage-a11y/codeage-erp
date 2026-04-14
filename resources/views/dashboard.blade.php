@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-header role-dashboard-header">
    <div>
        <h1>{{ $dashboard['title'] }}</h1>
        <p>{{ $dashboard['subtitle'] }}</p>
    </div>
    <span class="dashboard-role-pill">{{ $dashboard['role_label'] }}</span>
</div>

@if(! empty($dashboard['stats']))
    <div class="stats-grid dashboard-stats-grid">
        @foreach($dashboard['stats'] as $stat)
            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">{{ $stat['label'] }}</span>
                    <div class="stat-value">{{ $stat['value'] }}</div>
                    @if(! empty($stat['helper']))
                        <span class="dashboard-stat-helper">{{ $stat['helper'] }}</span>
                    @endif
                </div>
                <div class="stat-icon-wrapper {{ $stat['color'] }}">
                    <i data-lucide="{{ $stat['icon'] }}"></i>
                </div>
            </div>
        @endforeach
    </div>
@endif

@include('dashboard.partials.' . $dashboard['view'], ['dashboard' => $dashboard])

@if(! empty($dashboard['quick_actions']))
    <div class="quick-actions-section">
        <h3>Quick Actions</h3>
        <div class="quick-actions-grid dashboard-actions-grid">
            @foreach($dashboard['quick_actions'] as $action)
                <a href="{{ $action['href'] }}" class="action-card dashboard-action-card">
                    <div class="action-icon-wrapper {{ $action['tone'] }}">
                        <i data-lucide="{{ $action['icon'] }}"></i>
                    </div>
                    <div class="action-details">
                        <h4>{{ $action['label'] }}</h4>
                        <p>{{ $action['description'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif
@endsection
