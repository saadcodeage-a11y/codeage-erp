@extends('layouts.app')

@section('title', 'Self Service')

@php
    $formatMoney = fn ($value) => 'PKR ' . number_format((float) ($value ?? 0), 2);
@endphp

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>Self Service</h1>
        <p>Read-only HR information, current month attendance, leave requests, and finalized reviews.</p>
    </div>
</div>

@if(session('success') || $errors->any())
    <div class="portal-alert-stack">
        @if(session('success'))
            <div class="portal-alert success"><i data-lucide="circle-check-big"></i><span>{{ session('success') }}</span></div>
        @endif
        @if($errors->any())
            <div class="portal-alert error"><i data-lucide="alert-circle"></i><span>{{ $errors->first() }}</span></div>
        @endif
    </div>
@endif

@if(! $employee)
    <div class="table-card self-service-empty">
        <div class="empty-icon"><i data-lucide="shield-alert"></i></div>
        <h2>Employee Profile Not Linked</h2>
        <p>Your user account is not linked to an employee profile yet. HR needs to connect this account before self-service data can be shown.</p>
    </div>
@else
    <div class="self-service-hero">
        <div class="hero-identity">
            <div class="hero-avatar">{{ strtoupper(mb_substr($employee->full_name, 0, 2)) }}</div>
            <div>
                <div class="hero-title-row">
                    <h2>{{ $employee->full_name }}</h2>
                    <span class="status-pill {{ $employee->status }}">{{ ucfirst($employee->status) }}</span>
                </div>
                <p>{{ $employee->employee_id ?: 'Pending ID' }} | {{ $employee->designation ?: 'No designation' }}</p>
                <div class="hero-meta">
                    <span>{{ $employee->department?->name ?? 'Unassigned Department' }}</span>
                    <span>{{ $employee->teamManager?->name ? 'Manager: ' . $employee->teamManager->name : 'Manager not assigned' }}</span>
                </div>
            </div>
        </div>
        <div class="hero-shift-card">
            <span>Effective Shift</span>
            <strong>{{ $employee->effective_shift_start_time ? \Illuminate\Support\Carbon::createFromFormat('H:i:s', $employee->effective_shift_start_time)->format('g:i A') : '--:--' }} to {{ $employee->effective_shift_end_time ? \Illuminate\Support\Carbon::createFromFormat('H:i:s', $employee->effective_shift_end_time)->format('g:i A') : '--:--' }}</strong>
            <small>{{ $employee->shift_start_time || $employee->shift_end_time ? 'Custom employee timing' : 'Using system default timing' }}</small>
        </div>
    </div>

    <div class="stats-grid self-service-stats">
        <div class="stat-card"><div class="stat-content"><span class="stat-label">Latest Net Salary</span><span class="stat-value">{{ $portalStats['latestNetSalary'] !== null ? $formatMoney($portalStats['latestNetSalary']) : 'N/A' }}</span></div></div>
        <div class="stat-card"><div class="stat-content"><span class="stat-label">Security Balance</span><span class="stat-value">{{ $portalStats['securityBalance'] !== null ? $formatMoney($portalStats['securityBalance']) : 'N/A' }}</span></div></div>
        <div class="stat-card"><div class="stat-content"><span class="stat-label">{{ $currentMonthLabel }} Attendance</span><span class="stat-value">{{ $portalStats['currentMonthAttendanceRows'] }}</span></div></div>
        <div class="stat-card"><div class="stat-content"><span class="stat-label">Finalized Reviews</span><span class="stat-value">{{ $portalStats['finalizedReviews'] }}</span></div></div>
    </div>

    <div class="tabs-container self-service-tabs">
        @foreach(['profile' => 'Profile', 'salary' => 'Salary History', 'security' => 'Security Fund', 'tax' => 'Tax Records', 'attendance' => 'Attendance', 'leave' => 'Leave', 'performance' => 'Performance'] as $tabKey => $label)
            <a href="{{ route('self-service.index', ['tab' => $tabKey]) }}" class="tab-item {{ $activeTab === $tabKey ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @include('self-service.partials.profile')
    @include('self-service.partials.salary')
    @include('self-service.partials.security')
    @include('self-service.partials.tax')
    @include('self-service.partials.attendance')
    @include('self-service.partials.leave')
    @include('self-service.partials.performance')
@endif

<style>
    .portal-alert-stack{display:flex;flex-direction:column;gap:.75rem;margin-bottom:1rem}.portal-alert{display:flex;align-items:center;gap:.75rem;padding:.95rem 1rem;border-radius:14px;border:1px solid #e5e7eb;background:#fff}.portal-alert.success{border-color:#bbf7d0;background:#f0fdf4;color:#166534}.portal-alert.error{border-color:#fecaca;background:#fef2f2;color:#b91c1c}.portal-alert i{width:18px;height:18px}.self-service-empty{padding:3rem 2rem;text-align:center}.empty-icon{width:64px;height:64px;margin:0 auto 1rem;border-radius:20px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 100%);color:#f97316;border:1px solid #fed7aa}.empty-icon i{width:28px;height:28px}.self-service-hero{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(280px,.8fr);gap:1rem;margin-bottom:1.25rem}.hero-identity,.hero-shift-card,.mini-stat,.sub-card,.performance-card,.feedback-card,.info-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 24px rgba(15,23,42,.04)}.hero-identity,.hero-shift-card{padding:1.5rem}.hero-identity{display:flex;align-items:center;gap:1rem}.hero-avatar{width:72px;height:72px;border-radius:24px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#ff6b2c 0%,#ff4d1f 100%);color:#fff;font-size:1.75rem;font-weight:700;flex-shrink:0}.hero-title-row{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:.35rem}.hero-title-row h2{margin:0;font-size:1.9rem;color:#111827}.hero-identity p{margin:0;color:#6b7280}.hero-meta{display:flex;flex-wrap:wrap;gap:.75rem;margin-top:.85rem}.hero-meta span,.status-pill{display:inline-flex;align-items:center;padding:.35rem .75rem;border-radius:999px;font-size:.78rem;font-weight:700;border:1px solid #e5e7eb;background:#f8fafc;color:#475467;white-space:nowrap}.hero-shift-card{display:flex;flex-direction:column;justify-content:center;gap:.45rem;background:linear-gradient(180deg,#ffffff 0%,#fffaf5 100%);border-color:#fed7aa}.hero-shift-card span,.hero-shift-card small{color:#6b7280}.hero-shift-card strong{font-size:1.1rem;color:#111827}.self-service-stats,.self-service-tabs{margin-bottom:1.25rem}.hidden-tab{display:none}.section-head{margin-bottom:1rem}.section-head h2{margin:0 0 .35rem;font-size:1.125rem;color:#111827}.section-head p,.muted-text,.empty-copy{margin:0;color:#6b7280}.info-grid,.attendance-summary-grid,.metric-grid,.self-service-two-col{display:grid;gap:1rem}.info-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.info-card{padding:1rem 1.1rem}.info-card .label{display:block;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin-bottom:.45rem}.info-card strong{color:#111827;font-size:1rem;line-height:1.5}.attendance-summary-grid{grid-template-columns:repeat(6,minmax(0,1fr));margin-bottom:1rem}.leave-summary-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.mini-stat{padding:1rem}.mini-stat span{display:block;color:#6b7280;font-size:.84rem;margin-bottom:.4rem}.mini-stat strong{font-size:1.4rem;color:#111827}.self-service-two-col{grid-template-columns:repeat(2,minmax(0,1fr))}.sub-card{padding:1.25rem}.sub-card h3,.performance-card h3{margin:0 0 .85rem;font-size:1rem;color:#111827}.table-card + .table-card{margin-top:1.25rem}.stack-list{display:flex;flex-direction:column;gap:.9rem}.stack-item{border:1px solid #e5e7eb;border-radius:14px;padding:1rem;background:#fafafa}.stack-item-head,.performance-card-head,.performance-footer{display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start}.stack-item p,.performance-card p{margin:.2rem 0 0;color:#6b7280}.review-note{margin-top:.75rem;padding:.75rem;border-radius:12px;background:#fff;color:#475467;font-size:.92rem}.metric-grid{grid-template-columns:repeat(5,minmax(0,1fr));margin:1rem 0}.metric-chip{padding:.9rem;border-radius:14px;background:#f8fafc;border:1px solid #e2e8f0}.metric-chip span{display:block;color:#6b7280;font-size:.82rem;margin-bottom:.35rem}.metric-chip strong{color:#111827;font-size:1rem}.feedback-grid{margin-top:1rem}.feedback-card{padding:1rem;background:#fcfcfd}.feedback-card span{display:block;font-size:.82rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin-bottom:.45rem}.feedback-card p{margin:0;color:#111827;line-height:1.6}.performance-card{padding:1.2rem}.performance-footer{margin-top:1rem;padding-top:1rem;border-top:1px solid #e5e7eb;color:#475467;font-size:.92rem}.portal-form{display:flex;flex-direction:column;gap:1rem}.portal-form .form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.portal-form .form-group{display:flex;flex-direction:column;gap:.5rem}.portal-form label{font-weight:600;color:#374151}.portal-form input,.portal-form select,.portal-form textarea{width:100%;padding:.9rem 1rem;border:1px solid #dbe2ea;border-radius:14px;background:#fff;color:#111827;outline:none}.portal-form textarea{resize:vertical}.portal-form input:focus,.portal-form select:focus,.portal-form textarea:focus{border-color:#fb923c;box-shadow:0 0 0 4px rgba(251,146,60,.12)}.status-pill.active,.status-pill.approved,.status-pill.present,.status-pill.finalized{background:#ecfdf3;border-color:#bbf7d0;color:#15803d}.status-pill.pending,.status-pill.late{background:#fff7ed;border-color:#fed7aa;color:#c2410c}.status-pill.rejected,.status-pill.cancelled,.status-pill.absent,.status-pill.inactive,.status-pill.terminated,.status-pill.resigned{background:#fef2f2;border-color:#fecaca;color:#b91c1c}.status-pill.holiday,.status-pill.weekend,.status-pill.incomplete{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}@media (max-width:1200px){.self-service-hero,.self-service-two-col{grid-template-columns:1fr}.info-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.attendance-summary-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.metric-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media (max-width:768px){.hero-identity{flex-direction:column;align-items:flex-start}.info-grid,.attendance-summary-grid,.leave-summary-grid,.metric-grid,.portal-form .form-row{grid-template-columns:1fr}}
</style>
@endsection
