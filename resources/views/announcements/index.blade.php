@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
@php
    $announcementStatuses = [
        'active' => 'Active',
        'inactive' => 'Paused',
    ];
@endphp

<div class="page-header">
    <div class="header-left">
        <h1>Announcements</h1>
        <p>Share department-targeted or global updates with the right people.</p>
    </div>
    <div class="header-right">
        @if($canCreateAnnouncements)
            <button class="btn btn-primary" onclick="openAnnouncementModal()">
                <i data-lucide="megaphone"></i> New Announcement
            </button>
        @endif
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Visible Announcements</span><span class="stat-value">{{ $stats['total'] }}</span></div><div class="stat-icon-wrapper orange"><i data-lucide="megaphone"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Global</span><span class="stat-value">{{ $stats['global'] }}</span></div><div class="stat-icon-wrapper blue"><i data-lucide="globe"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Department Targeted</span><span class="stat-value">{{ $stats['department'] }}</span></div><div class="stat-icon-wrapper yellow"><i data-lucide="building-2"></i></div></div>
    <div class="stat-card"><div class="stat-content"><span class="stat-label">Active</span><span class="stat-value">{{ $stats['active'] }}</span></div><div class="stat-icon-wrapper green"><i data-lucide="badge-check"></i></div></div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="{{ route('announcements.index') }}" class="announcement-filter-form">
        <div class="form-group" style="margin: 0;">
            <label>Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title, message, department, or creator">
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Department</label>
            <select name="department">
                <option value="">All audiences</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) request('department') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Status</label>
            <select name="status">
                <option value="">All statuses</option>
                @foreach($announcementStatuses as $statusKey => $statusLabel)
                    <option value="{{ $statusKey }}" @selected(request('status') === $statusKey)>{{ $statusLabel }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-outline">
            <i data-lucide="filter"></i> Apply Filters
        </button>
    </form>
</div>

@if($announcements->count())
    <div class="announcement-list">
        @foreach($announcements as $announcement)
            @php
                $audiences = $announcement->is_global
                    ? collect(['Global'])
                    : $announcement->departments->pluck('name');
                $editPayload = [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'message' => $announcement->message,
                    'is_global' => $announcement->is_global,
                    'is_active' => $announcement->is_active,
                    'department_ids' => $announcement->departments->pluck('id')->all(),
                ];
            @endphp
            <div class="announcement-card">
                <div class="announcement-card-header">
                    <div>
                        <div class="announcement-meta-row">
                            <h2>{{ $announcement->title }}</h2>
                            <span class="status-badge {{ $announcement->is_active ? 'active' : 'inactive' }}">
                                {{ $announcement->is_active ? 'Active' : 'Paused' }}
                            </span>
                        </div>
                        <p>
                            {{ optional($announcement->published_at ?? $announcement->created_at)->format('d M Y, h:i A') }}
                            @if($announcement->creator)
                                | {{ $announcement->creator->name }}
                            @endif
                        </p>
                    </div>
                    @if($canEditAnnouncements)
                        <div class="action-buttons">
                            <button class="btn-action outline" onclick='openAnnouncementModal(@json($editPayload))'>
                                <i data-lucide="edit-2"></i> Edit
                            </button>
                            <button class="btn-action outline-red" onclick="deleteAnnouncement({{ $announcement->id }}, @json($announcement->title))">
                                <i data-lucide="trash-2"></i> Delete
                            </button>
                        </div>
                    @endif
                </div>
                <div class="announcement-audience-row">
                    @foreach($audiences as $audience)
                        <span class="summary-pill {{ $audience === 'Global' ? '' : 'muted' }}">{{ $audience }}</span>
                    @endforeach
                </div>
                <p class="announcement-message">{{ $announcement->message }}</p>
            </div>
        @endforeach
    </div>

    <div class="pagination-wrapper">{{ $announcements->links() }}</div>
@else
    <div class="card">
        <div class="empty-state-panel">No announcements matched the current filters.</div>
    </div>
@endif

@if($canCreateAnnouncements || $canEditAnnouncements)
    <div id="announcementModal" class="modal-overlay" style="display: none;">
        <div class="modal-container" style="max-width: 760px;">
            <div class="modal-header">
                <div>
                    <h2 id="announcementModalTitle">New Announcement</h2>
                    <p class="modal-desc" style="margin-bottom: 0;">Choose a global audience or target one or more departments.</p>
                </div>
                <button class="close-btn" onclick="closeModal('announcementModal')"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <form id="announcementForm" class="modal-form">
                    @csrf
                    <input type="hidden" name="announcement_id" id="announcement_id">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" id="announcement_title" required maxlength="255">
                    </div>
                    <div class="form-group">
                        <label>Announcement Message</label>
                        <textarea name="message" id="announcement_message" rows="6" required maxlength="5000"></textarea>
                    </div>
                    <div class="announcement-toggle-grid">
                        <label class="checkbox-row checkbox-card">
                            <input type="checkbox" name="is_global" id="announcement_is_global" value="1">
                            <span>
                                <strong>Global Announcement</strong>
                                <small>Everyone with announcement access can see it.</small>
                            </span>
                        </label>
                        <label class="checkbox-row checkbox-card">
                            <input type="checkbox" name="is_active" id="announcement_is_active" value="1" checked>
                            <span>
                                <strong>Active Now</strong>
                                <small>Inactive announcements stay hidden from read-only users.</small>
                            </span>
                        </label>
                    </div>
                    <div class="form-group" id="announcementDepartmentGroup">
                        <label>Target Departments</label>
                        <div class="department-checkbox-grid">
                            @forelse($departments as $department)
                                <label class="checkbox-row department-choice">
                                    <input type="checkbox" name="department_ids[]" value="{{ $department->id }}">
                                    <span>{{ $department->name }}</span>
                                </label>
                            @empty
                                <div class="empty-state-panel">Create departments first to target announcement audiences.</div>
                            @endforelse
                        </div>
                        <span class="hint">If Global is not enabled, select at least one department.</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('announcementModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" form="announcementForm">Save Announcement</button>
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

        function syncAnnouncementDepartmentState() {
            const isGlobal = document.getElementById('announcement_is_global').checked;
            const group = document.getElementById('announcementDepartmentGroup');
            group.style.opacity = isGlobal ? '0.55' : '1';
            group.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                checkbox.disabled = isGlobal;
                if (isGlobal) {
                    checkbox.checked = false;
                }
            });
        }

        function openAnnouncementModal(announcement = null) {
            const form = document.getElementById('announcementForm');
            form.reset();
            document.getElementById('announcement_id').value = announcement?.id || '';
            document.getElementById('announcement_title').value = announcement?.title || '';
            document.getElementById('announcement_message').value = announcement?.message || '';
            document.getElementById('announcement_is_global').checked = Boolean(announcement?.is_global);
            document.getElementById('announcement_is_active').checked = announcement ? Boolean(announcement.is_active) : true;

            form.querySelectorAll('input[name="department_ids[]"]').forEach((checkbox) => {
                checkbox.checked = Boolean(announcement?.department_ids?.includes(Number(checkbox.value)));
            });

            document.getElementById('announcementModalTitle').textContent = announcement ? 'Edit Announcement' : 'New Announcement';
            syncAnnouncementDepartmentState();
            openModal('announcementModal');
        }

        async function submitJson(url, method, data) {
            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: data,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Request failed.');
            }

            return payload;
        }

        document.getElementById('announcement_is_global').addEventListener('change', syncAnnouncementDepartmentState);

        document.getElementById('announcementForm').addEventListener('submit', async function (event) {
            event.preventDefault();

            const announcementId = document.getElementById('announcement_id').value;
            const formData = new FormData(this);

            if (announcementId) {
                formData.append('_method', 'PUT');
            }

            try {
                const payload = await submitJson(
                    announcementId ? `/announcements/${announcementId}` : '{{ route('announcements.store') }}',
                    'POST',
                    formData
                );

                sessionStorage.setItem('flash_message', payload.message);
                sessionStorage.setItem('flash_type', 'success');
                window.location.reload();
            } catch (error) {
                window.showToast(error.message, 'error');
            }
        });

        async function deleteAnnouncement(id, title) {
            if (!confirm(`Delete the announcement "${title}"?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('_method', 'DELETE');

            try {
                const payload = await submitJson(`/announcements/${id}`, 'POST', formData);
                sessionStorage.setItem('flash_message', payload.message);
                sessionStorage.setItem('flash_type', 'success');
                window.location.reload();
            } catch (error) {
                window.showToast(error.message, 'error');
            }
        }

        window.addEventListener('click', function (event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        });
    </script>
@endif

<style>
    .announcement-filter-form {
        display: grid;
        grid-template-columns: minmax(280px, 1.4fr) minmax(200px, 0.8fr) minmax(180px, 0.8fr) auto;
        gap: 16px;
        align-items: end;
    }
    .announcement-list {
        display: grid;
        gap: 18px;
    }
    .announcement-card {
        padding: 22px 24px;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.04);
    }
    .announcement-card-header {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
        margin-bottom: 14px;
    }
    .announcement-meta-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }
    .announcement-meta-row h2 {
        margin: 0;
        font-size: 22px;
        color: #111827;
    }
    .announcement-card-header p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }
    .announcement-audience-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .announcement-message {
        margin: 0;
        white-space: pre-line;
        color: #374151;
        line-height: 1.7;
        font-size: 14px;
    }
    .announcement-toggle-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }
    .checkbox-card {
        padding: 14px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #f9fafb;
        align-items: flex-start;
    }
    .checkbox-card span {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .checkbox-card strong {
        color: #111827;
        font-size: 14px;
    }
    .checkbox-card small {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.5;
    }
    .department-checkbox-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        padding: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
    }
    .department-choice {
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
    }
    .modal-form textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #f9fafb;
        resize: vertical;
        min-height: 140px;
    }
    @media (max-width: 980px) {
        .announcement-filter-form,
        .announcement-toggle-grid {
            grid-template-columns: 1fr;
        }
        .announcement-card-header {
            flex-direction: column;
        }
    }
</style>
@endsection
