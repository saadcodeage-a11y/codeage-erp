@extends('layouts.app')

@section('title', 'Performance Evaluation')

@section('content')
<div class="page-header">
    <div class="header-left">
        <a href="{{ route('performance.index') }}" class="btn btn-outline" style="margin-bottom: 12px;">
            <i data-lucide="arrow-left"></i> Back to Performance
        </a>
        <h1>{{ $evaluation->employee?->full_name ?? 'Performance Evaluation' }}</h1>
        <p>{{ $evaluation->periodLabel() }} | {{ \App\Models\PerformanceEvaluation::types()[$evaluation->evaluation_type] ?? ucfirst($evaluation->evaluation_type) }}</p>
    </div>
    <div class="header-right">
        <span class="status-badge {{ $evaluation->status }}">{{ \App\Models\PerformanceEvaluation::statuses()[$evaluation->status] ?? ucfirst(str_replace('_', ' ', $evaluation->status)) }}</span>
    </div>
</div>

@if(session('success'))
    <div class="status-banner success" style="margin-bottom: 24px;">
        <i data-lucide="circle-check-big"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="stats-grid">
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Employee</span><span class="stat-value" style="font-size: 24px;">{{ $evaluation->employee?->employee_id ?? 'N/A' }}</span></div><div class="stat-icon-wrapper orange"><i data-lucide="badge-info"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Manager Score</span><span class="stat-value">{{ $evaluation->managerAverage() !== null ? number_format($evaluation->managerAverage(), 2) : 'Pending' }}</span></div><div class="stat-icon-wrapper blue"><i data-lucide="star"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">HR Final Score</span><span class="stat-value">{{ $evaluation->hrAverage() !== null ? number_format($evaluation->hrAverage(), 2) : 'Pending' }}</span></div><div class="stat-icon-wrapper green"><i data-lucide="shield-check"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Manager</span><span class="stat-value" style="font-size: 22px;">{{ $evaluation->manager?->name ?? ($evaluation->employee?->teamManager?->name ?? 'Not assigned') }}</span></div><div class="stat-icon-wrapper yellow"><i data-lucide="user-round-cog"></i></div></div>
</div>

<div class="two-column-layout">
    <div class="card">
        <div class="section-header">
            <div>
                <h2>Manager Contribution</h2>
                <p>Monthly or bi-annual manager evaluation across the five defined metrics.</p>
            </div>
        </div>
        <form action="{{ route('performance.manager.update', $evaluation) }}" method="POST" class="modal-form">
            @csrf
            <div class="metrics-grid">
                @foreach($metricLabels as $metricKey => $metricLabel)
                    <div class="form-group">
                        <label>{{ $metricLabel }}</label>
                        <select name="manager_{{ $metricKey }}" required @disabled(! $canEditManagerContribution || $evaluation->status === \App\Models\PerformanceEvaluation::STATUS_FINALIZED)>
                            <option value="">Select</option>
                            @for($score = 1; $score <= 5; $score++)
                                <option value="{{ $score }}" @selected(old('manager_' . $metricKey, $evaluation->{'manager_' . $metricKey}) == $score)>{{ $score }} / 5</option>
                            @endfor
                        </select>
                    </div>
                @endforeach
                <div class="form-group full-width">
                    <label>Manager Feedback</label>
                    <textarea name="manager_feedback" rows="6" required @disabled(! $canEditManagerContribution || $evaluation->status === \App\Models\PerformanceEvaluation::STATUS_FINALIZED)>{{ old('manager_feedback', $evaluation->manager_feedback) }}</textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding: 0; margin-top: 8px;">
                <button type="submit" class="btn btn-primary" @disabled(! $canEditManagerContribution || $evaluation->status === \App\Models\PerformanceEvaluation::STATUS_FINALIZED)>Save Manager Contribution</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-header">
            <div>
                <h2>HR Finalization</h2>
                <p>HR reviews manager input, applies final ratings, and closes the evaluation cycle.</p>
            </div>
        </div>

        @if($canFinalizeEvaluations)
            <form action="{{ route('performance.finalize', $evaluation) }}" method="POST" class="modal-form">
                @csrf
                <div class="metrics-grid">
                    @foreach($metricLabels as $metricKey => $metricLabel)
                        <div class="form-group">
                            <label>{{ $metricLabel }}</label>
                            <select name="hr_{{ $metricKey }}" required @disabled($evaluation->status === \App\Models\PerformanceEvaluation::STATUS_FINALIZED)>
                                <option value="">Select</option>
                                @for($score = 1; $score <= 5; $score++)
                                    <option value="{{ $score }}" @selected(old('hr_' . $metricKey, $evaluation->{'hr_' . $metricKey}) == $score)>{{ $score }} / 5</option>
                                @endfor
                            </select>
                        </div>
                    @endforeach
                    <div class="form-group full-width">
                        <label>HR Final Feedback</label>
                        <textarea name="hr_feedback" rows="6" required @disabled($evaluation->status === \App\Models\PerformanceEvaluation::STATUS_FINALIZED)>{{ old('hr_feedback', $evaluation->hr_feedback) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 0; margin-top: 8px;">
                    <button type="submit" class="btn btn-primary" @disabled($evaluation->status === \App\Models\PerformanceEvaluation::STATUS_FINALIZED)>Finalize Evaluation</button>
                </div>
            </form>
        @else
            <div class="empty-state-panel">
                Only HR can finalize evaluations. Manager contribution is shown here for reference.
            </div>
        @endif
    </div>
</div>

<div class="card" style="margin-top: 24px;">
    <div class="section-header">
        <div>
            <h2>Historical Performance Tracking</h2>
            <p>Previous evaluation periods and finalized review history for this employee.</p>
        </div>
    </div>

    <div class="history-list">
        @forelse($history as $item)
            <div class="history-card">
                <div class="history-card-header">
                    <div>
                        <strong>{{ $item->periodLabel() }}</strong>
                        <p>{{ \App\Models\PerformanceEvaluation::types()[$item->evaluation_type] ?? ucfirst($item->evaluation_type) }} | {{ \App\Models\PerformanceEvaluation::statuses()[$item->status] ?? ucfirst(str_replace('_', ' ', $item->status)) }}</p>
                    </div>
                    <div class="history-metrics">
                        <span class="summary-pill muted">Manager: {{ $item->managerAverage() !== null ? number_format($item->managerAverage(), 2) : 'N/A' }}</span>
                        <span class="summary-pill">{{ $item->hrAverage() !== null ? 'HR: ' . number_format($item->hrAverage(), 2) : 'HR Pending' }}</span>
                    </div>
                </div>
                <div class="history-copy">
                    <p><strong>Manager Feedback:</strong> {{ $item->manager_feedback ?: 'Not submitted yet.' }}</p>
                    <p><strong>HR Feedback:</strong> {{ $item->hr_feedback ?: 'Not finalized yet.' }}</p>
                </div>
                <a href="{{ route('performance.show', $item) }}" class="btn-action outline">
                    <i data-lucide="external-link"></i> Open Evaluation
                </a>
            </div>
        @empty
            <div class="empty-state-panel">No historical evaluations found for this employee yet.</div>
        @endforelse
    </div>
</div>

<style>
    .two-column-layout {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
        align-items: start;
    }
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .history-list {
        display: grid;
        gap: 16px;
    }
    .history-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px 18px;
        background: #fff;
        display: grid;
        gap: 14px;
    }
    .history-card-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
    }
    .history-card-header p,
    .history-copy p {
        margin: 4px 0 0;
        color: #6b7280;
        line-height: 1.6;
    }
    .history-metrics {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .status-banner {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534;
    }
    @media (max-width: 1100px) {
        .two-column-layout,
        .metrics-grid {
            grid-template-columns: 1fr;
        }
        .history-card-header {
            flex-direction: column;
        }
    }
</style>
@endsection
