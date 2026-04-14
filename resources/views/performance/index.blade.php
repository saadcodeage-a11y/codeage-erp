@extends('layouts.app')

@section('title', 'Performance')

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>Performance</h1>
        <p>Manage monthly and bi-annual evaluations with manager contribution and HR finalization.</p>
    </div>
    <div class="header-right">
        <button class="btn btn-primary" onclick="openModal('performanceCreateModal')">
            <i data-lucide="plus"></i> New Evaluation
        </button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Total Evaluations</span><span class="stat-value">{{ $stats['total'] }}</span></div><div class="stat-icon-wrapper orange"><i data-lucide="clipboard-list"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Monthly</span><span class="stat-value">{{ $stats['monthly'] }}</span></div><div class="stat-icon-wrapper blue"><i data-lucide="calendar-days"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Bi-Annual</span><span class="stat-value">{{ $stats['biannual'] }}</span></div><div class="stat-icon-wrapper yellow"><i data-lucide="calendar-range"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Pending HR</span><span class="stat-value">{{ $stats['pending_hr'] }}</span></div><div class="stat-icon-wrapper red"><i data-lucide="badge-alert"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Finalized</span><span class="stat-value">{{ $stats['finalized'] }}</span></div><div class="stat-icon-wrapper green"><i data-lucide="badge-check"></i></div></div>
</div>

<div class="search-container">
    <form action="{{ route('performance.index') }}" method="GET" class="search-form" style="grid-template-columns: minmax(240px, 1fr) 180px 180px auto auto;">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by employee name, ID, or designation..." class="search-input">
        <select name="type" class="announcement-inline-select">
            <option value="">All types</option>
            @foreach(\App\Models\PerformanceEvaluation::types() as $typeKey => $typeLabel)
                <option value="{{ $typeKey }}" @selected(request('type') === $typeKey)>{{ $typeLabel }}</option>
            @endforeach
        </select>
        <select name="status" class="announcement-inline-select">
            <option value="">All statuses</option>
            @foreach(\App\Models\PerformanceEvaluation::statuses() as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}" @selected(request('status') === $statusKey)>{{ $statusLabel }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline"><i data-lucide="filter"></i> Filter</button>
        @if(request()->filled('search') || request()->filled('type') || request()->filled('status'))
            <a href="{{ route('performance.index') }}" class="btn btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Type</th>
                <th>Period</th>
                <th>Manager Score</th>
                <th>Final Score</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evaluations as $evaluation)
                <tr>
                    <td>
                        <div class="employee-cell">
                            <div class="avatar-sm orange">{{ strtoupper(substr($evaluation->employee?->full_name ?? 'NA', 0, 2)) }}</div>
                            <div class="employee-info">
                                <span class="emp-name">{{ $evaluation->employee?->full_name ?? 'Unknown employee' }}</span>
                                <span class="emp-email">{{ $evaluation->employee?->employee_id }} | {{ $evaluation->employee?->designation }}</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="summary-pill muted">{{ \App\Models\PerformanceEvaluation::types()[$evaluation->evaluation_type] ?? ucfirst($evaluation->evaluation_type) }}</span></td>
                    <td>{{ $evaluation->periodLabel() }}</td>
                    <td>{{ $evaluation->managerAverage() !== null ? number_format($evaluation->managerAverage(), 2) . ' / 5' : 'Not submitted' }}</td>
                    <td>{{ $evaluation->hrAverage() !== null ? number_format($evaluation->hrAverage(), 2) . ' / 5' : 'Pending HR' }}</td>
                    <td><span class="status-badge {{ $evaluation->status }}">{{ \App\Models\PerformanceEvaluation::statuses()[$evaluation->status] ?? ucfirst(str_replace('_', ' ', $evaluation->status)) }}</span></td>
                    <td>
                        <a href="{{ route('performance.show', $evaluation) }}" class="btn-action outline">
                            <i data-lucide="chart-no-axes-column"></i> Open
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No performance evaluations found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination-wrapper">{{ $evaluations->links() }}</div>
</div>

<div id="performanceCreateModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 720px;">
        <div class="modal-header">
            <div>
                <h2>New Performance Evaluation</h2>
                <p class="modal-desc" style="margin-bottom: 0;">Create a monthly or bi-annual evaluation for an employee.</p>
            </div>
            <button class="close-btn" type="button" onclick="closeModal('performanceCreateModal')"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <form action="{{ route('performance.store') }}" method="POST" class="modal-form" id="performanceCreateForm">
                @csrf
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Employee</label>
                        <select name="employee_id" required>
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }}{{ $employee->employee_id ? ' (' . $employee->employee_id . ')' : '' }}{{ $employee->designation ? ' | ' . $employee->designation : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Evaluation Type</label>
                        <select name="evaluation_type" id="evaluation_type" required onchange="toggleEvaluationPeriodFields()">
                            <option value="{{ \App\Models\PerformanceEvaluation::TYPE_MONTHLY }}">Monthly</option>
                            <option value="{{ \App\Models\PerformanceEvaluation::TYPE_BIANNUAL }}">Bi-Annual</option>
                        </select>
                    </div>
                    <div class="form-group" id="monthlyPeriodGroup">
                        <label>Evaluation Month</label>
                        <input type="month" name="monthly_period" value="{{ now()->format('Y-m') }}">
                    </div>
                    <div class="form-group" id="biannualYearGroup" style="display: none;">
                        <label>Year</label>
                        <input type="number" name="biannual_year" min="2020" max="2100" value="{{ now()->year }}">
                    </div>
                    <div class="form-group" id="biannualHalfGroup" style="display: none;">
                        <label>Half</label>
                        <select name="biannual_half">
                            <option value="1">First Half (Jan - Jun)</option>
                            <option value="2">Second Half (Jul - Dec)</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('performanceCreateModal')">Cancel</button>
            <button type="submit" class="btn btn-primary" form="performanceCreateForm">Create Evaluation</button>
        </div>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function toggleEvaluationPeriodFields() {
        const type = document.getElementById('evaluation_type').value;
        const isBiannual = type === '{{ \App\Models\PerformanceEvaluation::TYPE_BIANNUAL }}';

        document.getElementById('monthlyPeriodGroup').style.display = isBiannual ? 'none' : 'block';
        document.getElementById('biannualYearGroup').style.display = isBiannual ? 'block' : 'none';
        document.getElementById('biannualHalfGroup').style.display = isBiannual ? 'block' : 'none';
    }

    window.addEventListener('click', function (event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    });
</script>
@endsection
