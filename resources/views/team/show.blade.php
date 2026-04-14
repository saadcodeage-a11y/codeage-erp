@extends('layouts.app')

@section('title', 'Team Member Review')

@section('content')
<div class="page-header">
    <div class="header-left">
        <a href="{{ route('team.index') }}" class="btn btn-outline" style="margin-bottom: 12px;">
            <i data-lucide="arrow-left"></i> Back to My Team
        </a>
        <h1>{{ $employee->full_name }}</h1>
        <p>{{ $employee->employee_id }} | {{ $employee->designation ?: 'No designation' }}</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Department</span><span class="stat-value" style="font-size: 24px;">{{ $employee->department?->name ?? 'Unassigned' }}</span></div><div class="stat-icon-wrapper orange"><i data-lucide="building-2"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Status</span><span class="stat-value" style="font-size: 24px;">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</span></div><div class="stat-icon-wrapper green"><i data-lucide="badge-check"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Team Manager</span><span class="stat-value" style="font-size: 24px;">{{ $employee->teamManager?->name ?? 'Not assigned' }}</span></div><div class="stat-icon-wrapper blue"><i data-lucide="user-round-cog"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Reviews Logged</span><span class="stat-value">{{ $employee->performanceReviews->count() }}</span></div><div class="stat-icon-wrapper yellow"><i data-lucide="message-square-text"></i></div></div>
</div>

@if(session('success'))
    <div class="status-banner success" style="margin-bottom: 24px;">
        <i data-lucide="circle-check-big"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="two-column-layout">
    <div class="card">
        <div class="section-header">
            <div>
                <h2>Performance Review</h2>
                <p>Submit a monthly rating and feedback for this team member.</p>
            </div>
        </div>

        <form action="{{ route('team.reviews.store', $employee) }}" method="POST" class="modal-form">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label>Review Month</label>
                    <input type="month" name="review_month" value="{{ old('review_month', $currentReviewMonth) }}" required>
                </div>
                <div class="form-group">
                    <label>Rating</label>
                    <select name="rating" required>
                        <option value="">Select rating</option>
                        @for($rating = 1; $rating <= 5; $rating++)
                            <option value="{{ $rating }}" @selected((string) old('rating') === (string) $rating)>{{ $rating }} / 5</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Feedback</label>
                    <textarea name="feedback" rows="6" required placeholder="Add performance feedback, coaching notes, or improvement points...">{{ old('feedback') }}</textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding: 0; margin-top: 8px;">
                <button type="submit" class="btn btn-primary">Save Review</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="section-header">
            <div>
                <h2>Recent Attendance</h2>
                <p>Latest attendance rows for context while reviewing performance.</p>
            </div>
        </div>

        <div class="review-side-list">
            @forelse($employee->attendanceRecords as $record)
                <div class="review-side-item">
                    <strong>{{ $record->attendance_date->format('d M Y') }}</strong>
                    <span>{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span>
                </div>
            @empty
                <div class="empty-state-panel">No attendance records found for this employee.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="card" style="margin-top: 24px;">
    <div class="section-header">
        <div>
            <h2>Review History</h2>
            <p>Previous monthly ratings and feedback submitted for this employee.</p>
        </div>
    </div>

    <div class="review-history-list">
        @forelse($employee->performanceReviews as $review)
            <div class="review-history-card">
                <div class="review-history-header">
                    <div>
                        <strong>{{ $review->review_month->format('F Y') }}</strong>
                        <p>{{ $review->manager?->name ?? 'Unknown manager' }}</p>
                    </div>
                    <span class="summary-pill">{{ $review->rating }}/5 Rating</span>
                </div>
                <p>{{ $review->feedback }}</p>
            </div>
        @empty
            <div class="empty-state-panel">No performance reviews have been submitted yet.</div>
        @endforelse
    </div>
</div>

<style>
    .two-column-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.85fr);
        gap: 24px;
        align-items: start;
    }
    .review-side-list,
    .review-history-list {
        display: grid;
        gap: 14px;
    }
    .review-side-item,
    .review-history-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 14px 16px;
        background: #fff;
    }
    .review-side-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
    }
    .review-side-item span {
        color: #6b7280;
        font-size: 13px;
    }
    .review-history-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 10px;
    }
    .review-history-header p,
    .review-history-card p {
        margin: 0;
        color: #6b7280;
        line-height: 1.6;
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
        .two-column-layout {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
