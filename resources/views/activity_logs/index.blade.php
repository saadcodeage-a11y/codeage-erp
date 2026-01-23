@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>System Activity Logs</h1>
        <p>Monitor all system actions, data changes, and authentication events</p>
    </div>
</div>

<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Type</th>
                <th>Subject</th>
                <th>IP Address</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $activity)
                <tr>
                    <td>
                        <div style="font-weight: 500; color: #111827;">{{ $activity->created_at->format('M j, Y') }}</div>
                        <div style="font-size: 12px; color: #6b7280;">{{ $activity->created_at->format('h:i A') }}</div>
                    </td>
                    <td>
                        @if($activity->user)
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; color: #4b5563; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">
                                    {{ strtoupper(substr($activity->user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 500; color: #111827;">{{ $activity->user->name }}</div>
                                    <div style="font-size: 12px; color: #6b7280;">{{ $activity->user->role }}</div>
                                </div>
                            </div>
                        @else
                            <span style="color: #9ca3af; font-style: italic;">System / Guest</span>
                        @endif
                    </td>
                    <td>
                        <span style="color: #374151; font-weight: 500;">{{ $activity->description }}</span>
                    </td>
                    <td>
                        @php
                            $badgeClass = match($activity->type) {
                                'success' => 'success',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                default => 'info'
                            };
                            $badgeStyles = match($activity->type) {
                                'success' => 'background: #ecfdf5; color: #059669;',
                                'warning' => 'background: #fffbeb; color: #d97706;',
                                'danger' => 'background: #fef2f2; color: #dc2626;',
                                default => 'background: #f0f7ff; color: #006adc;'
                            };
                        @endphp
                        <span style="padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 500; {{ $badgeStyles }}">
                            {{ ucfirst($activity->type) }}
                        </span>
                    </td>
                    <td>
                        @if($activity->subject)
                            <div style="font-size: 13px; color: #4b5563;">
                                {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                            </div>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <code style="font-size: 11px; background: #f9fafb; padding: 2px 6px; border-radius: 4px; color: #6b7280;">
                            {{ $activity->ip_address ?: 'N/A' }}
                        </code>
                    </td>
                    <td>
                        @if($activity->properties)
                            <button onclick="showActivityDetails({{ json_encode($activity->properties) }})" class="btn btn-outline" style="padding: 4px 8px; font-size: 12px;">
                                View Changes
                            </button>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 48px; color: #6b7280;">
                        No activity logs found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="pagination-wrapper" style="border-top: 1px solid #f3f4f6;">
        {{ $activities->links() }}
    </div>
</div>

<!-- Details Modal -->
<div id="activityDetailsModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 600px;">
        <div class="modal-header">
            <div>
                <h2>Activity Details</h2>
                <p class="modal-desc">Raw data and changes captured for this action.</p>
            </div>
            <button onclick="hideActivityDetails()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <pre id="activityPropertiesJson" style="background: #fdfdfc; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb; font-family: monospace; font-size: 12px; overflow-x: auto; max-height: 400px;"></pre>
        </div>
        <div class="modal-footer">
            <button onclick="hideActivityDetails()" class="btn btn-primary">Close</button>
        </div>
    </div>
</div>

<script>
    function showActivityDetails(properties) {
        const modal = document.getElementById('activityDetailsModal');
        const pre = document.getElementById('activityPropertiesJson');
        pre.textContent = JSON.stringify(properties, null, 4);
        modal.style.display = 'flex';
    }

    function hideActivityDetails() {
        document.getElementById('activityDetailsModal').style.display = 'none';
    }
</script>
@endsection
