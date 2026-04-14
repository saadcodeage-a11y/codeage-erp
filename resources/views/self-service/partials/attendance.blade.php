<section class="table-card {{ $activeTab === 'attendance' ? '' : 'hidden-tab' }}">
    <div class="section-head">
        <h2>Attendance</h2>
        <p>Current month attendance only for {{ $currentMonthLabel }}.</p>
    </div>

    <div class="attendance-summary-grid">
        <div class="mini-stat"><span>Present</span><strong>{{ $attendanceSummary['present'] }}</strong></div>
        <div class="mini-stat"><span>Late</span><strong>{{ $attendanceSummary['late'] }}</strong></div>
        <div class="mini-stat"><span>Absent</span><strong>{{ $attendanceSummary['absent'] }}</strong></div>
        <div class="mini-stat"><span>Incomplete</span><strong>{{ $attendanceSummary['incomplete'] }}</strong></div>
        <div class="mini-stat"><span>Holiday</span><strong>{{ $attendanceSummary['holiday'] }}</strong></div>
        <div class="mini-stat"><span>Weekend</span><strong>{{ $attendanceSummary['weekend'] }}</strong></div>
    </div>

    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Shift</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Late</th>
                    <th>Early</th>
                    <th>Work Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendanceRecords as $record)
                    <tr>
                        <td>{{ $record->attendance_date?->format('d M Y') }}</td>
                        <td><span class="status-pill {{ $record->status }}">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span></td>
                        <td>{{ $formatTime($record->shift_start_time) }} to {{ $formatTime($record->shift_end_time) }}</td>
                        <td>{{ $formatTime($record->clock_in) }}</td>
                        <td>{{ $formatTime($record->clock_out) }}</td>
                        <td>{{ $record->late_duration ?: '--' }}</td>
                        <td>{{ $record->early_duration ?: '--' }}</td>
                        <td>{{ $record->work_duration ?: '--' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">No attendance records are available for {{ $currentMonthLabel }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
