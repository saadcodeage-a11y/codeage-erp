@extends('layouts.app')

@section('title', 'Dashboard')

@php($todayLabel = now()->format('l, d M Y'))

@section('content')
<div class="rb-dashboard">
    <section class="rb-hero">
        <div class="rb-hero__copy">
            <span class="rb-hero__eyebrow">{{ $dashboard['role_label'] }} Workspace</span>
            <h1>{{ $dashboard['title'] }}</h1>
            <p>{{ $dashboard['subtitle'] }}</p>
        </div>
        <div class="rb-hero__meta">
            <div class="rb-hero__meta-card">
                <span>Today</span>
                <strong>{{ $todayLabel }}</strong>
            </div>
            <div class="rb-hero__meta-card rb-hero__meta-card--accent">
                <span>Role</span>
                <strong>{{ $dashboard['role_label'] }}</strong>
            </div>
        </div>
    </section>

    @if(! empty($dashboard['stats']))
        <section class="rb-stats">
            @foreach($dashboard['stats'] as $index => $stat)
                <article class="rb-stat rb-stat--{{ $stat['color'] }}">
                    <div class="rb-stat__top">
                        <span class="rb-stat__label">{{ $stat['label'] }}</span>
                        <div class="rb-stat__icon">
                            <i data-lucide="{{ $stat['icon'] }}"></i>
                        </div>
                    </div>
                    <div class="rb-stat__value">{{ $stat['value'] }}</div>
                    @if(! empty($stat['helper']))
                        <p class="rb-stat__helper">{{ $stat['helper'] }}</p>
                    @else
                        <p class="rb-stat__helper">Updated from current system data.</p>
                    @endif
                    <span class="rb-stat__accent rb-stat__accent--{{ $loop->index % 4 }}"></span>
                </article>
            @endforeach
        </section>
    @endif

    @include('dashboard.partials.' . $dashboard['view'], ['dashboard' => $dashboard])

    @if(! empty($dashboard['quick_actions']))
        <section class="rb-section">
            <div class="rb-section__head">
                <div>
                    <span class="rb-section__eyebrow">Actions</span>
                    <h2>Quick Actions</h2>
                    <p>Jump directly into the workflows that matter most for this role.</p>
                </div>
            </div>

            <div class="rb-actions">
                @foreach($dashboard['quick_actions'] as $action)
                    <a href="{{ $action['href'] }}" class="rb-action-card">
                        <div class="rb-action-card__icon rb-action-card__icon--{{ $action['tone'] }}">
                            <i data-lucide="{{ $action['icon'] }}"></i>
                        </div>
                        <div class="rb-action-card__body">
                            <strong>{{ $action['label'] }}</strong>
                            <p>{{ $action['description'] }}</p>
                        </div>
                        <i data-lucide="arrow-up-right" class="rb-action-card__arrow"></i>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
