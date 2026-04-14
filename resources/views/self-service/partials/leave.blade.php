<section class="table-card {{ $activeTab === 'leave' ? '' : 'hidden-tab' }}">
    <div class="section-head">
        <h2>Leave</h2>
        <p>Apply for leave and track the status of your requests.</p>
    </div>

    <div class="attendance-summary-grid leave-summary-grid">
        <div class="mini-stat"><span>Pending</span><strong>{{ $leaveSummary['pending'] }}</strong></div>
        <div class="mini-stat"><span>Approved</span><strong>{{ $leaveSummary['approved'] }}</strong></div>
        <div class="mini-stat"><span>Rejected</span><strong>{{ $leaveSummary['rejected'] }}</strong></div>
        <div class="mini-stat"><span>Cancelled</span><strong>{{ $leaveSummary['cancelled'] }}</strong></div>
    </div>

    <div class="self-service-two-col">
        <div class="sub-card">
            <h3>Apply for Leave</h3>
            <form action="{{ route('self-service.leaves.store') }}" method="POST" class="portal-form">
                @csrf
                <div class="form-group">
                    <label>Leave Type</label>
                    <select name="leave_type_id" required>
                        <option value="">Select leave type</option>
                        @foreach($leaveTypes as $leaveType)
                            <option value="{{ $leaveType->id }}" @selected(old('leave_type_id') == $leaveType->id)>{{ $leaveType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" rows="4" required>{{ old('reason') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Leave Request</button>
            </form>
        </div>

        <div class="sub-card">
            <h3>Recent Requests</h3>
            <div class="stack-list">
                @forelse($leaveRequests as $leaveRequest)
                    <div class="stack-item">
                        <div class="stack-item-head">
                            <strong>{{ $leaveRequest->leaveType?->name ?? 'Leave' }}</strong>
                            <span class="status-pill {{ $leaveRequest->status }}">{{ ucfirst($leaveRequest->status) }}</span>
                        </div>
                        <p>{{ $leaveRequest->start_date?->format('d M Y') }} to {{ $leaveRequest->end_date?->format('d M Y') }} | {{ $leaveRequest->days_count }} day(s)</p>
                        <small>{{ $leaveRequest->reason }}</small>
                        @if($leaveRequest->reviewer_notes)
                            <div class="review-note">Review note: {{ $leaveRequest->reviewer_notes }}</div>
                        @endif
                        @if($leaveRequest->status === 'pending')
                            <form action="{{ route('self-service.leaves.cancel', $leaveRequest) }}" method="POST" style="margin-top: 0.75rem;">
                                @csrf
                                <button type="submit" class="btn btn-outline small">Cancel Request</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="empty-copy">No leave requests have been submitted yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
