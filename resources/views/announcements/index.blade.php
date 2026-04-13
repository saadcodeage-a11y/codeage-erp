@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
@php
    $announcementStatuses = [
        'active' => 'Active',
        'inactive' => 'Paused',
    ];
    $announcementTypes = \App\Models\Announcement::types();
    $announcementDateModes = \App\Models\Announcement::dateModes();
@endphp

<div class="page-header">
    <div class="header-left">
        <h1>Announcements</h1>
        <p>Publish team updates, office notices, and official holidays to the right audience.</p>
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

<div class="card announcement-overview-card" style="margin-bottom: 24px;">
    <div class="section-header">
        <div>
            <h2>Announcement Filters</h2>
            <p>Refine the announcement list by audience, type, or current status.</p>
        </div>
        @if(request()->filled('search') || request()->filled('type') || request()->filled('department') || request()->filled('status'))
            <a href="{{ route('announcements.index') }}" class="btn btn-outline announcement-clear-btn">
                <i data-lucide="rotate-ccw"></i> Clear
            </a>
        @endif
    </div>
    <form method="GET" action="{{ route('announcements.index') }}" class="announcement-filter-form">
        <div class="form-group" style="margin: 0;">
            <label>Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title, message, department, or creator">
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Type</label>
            <select name="type">
                <option value="">All types</option>
                @foreach($announcementTypes as $typeKey => $typeLabel)
                    <option value="{{ $typeKey }}" @selected(request('type') === $typeKey)>{{ $typeLabel }}</option>
                @endforeach
            </select>
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
        <div class="announcement-filter-actions">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="filter"></i> Apply Filters
            </button>
        </div>
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
                    'announcement_type' => $announcement->announcement_type,
                    'date_mode' => $announcement->date_mode,
                    'event_date' => optional($announcement->event_date)->format('Y-m-d'),
                    'event_start_date' => optional($announcement->event_start_date)->format('Y-m-d'),
                    'event_end_date' => optional($announcement->event_end_date)->format('Y-m-d'),
                    'is_global' => $announcement->is_global,
                    'is_active' => $announcement->is_active,
                    'department_ids' => $announcement->departments->pluck('id')->all(),
                ];
            @endphp
            <div class="announcement-card">
                <div class="announcement-card-header">
                    <div class="announcement-main-copy">
                        <div class="announcement-meta-row">
                            <span class="type-chip {{ $announcement->announcement_type === \App\Models\Announcement::TYPE_OFFICIAL_HOLIDAY ? 'holiday' : 'general' }}">
                                {{ $announcementTypes[$announcement->announcement_type] ?? 'Announcement' }}
                            </span>
                            @if($announcement->eventDateLabel())
                                <span class="summary-pill muted">{{ $announcement->eventDateLabel() }}</span>
                            @endif
                            <span class="status-badge {{ $announcement->is_active ? 'active' : 'inactive' }}">
                                {{ $announcement->is_active ? 'Active' : 'Paused' }}
                            </span>
                        </div>
                        <h2>{{ $announcement->title }}</h2>
                        <p class="announcement-meta-text">
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
        <div class="announcement-empty-state">
            <div class="announcement-empty-icon">
                <i data-lucide="megaphone-off"></i>
            </div>
            <h3>No Announcements Found</h3>
            <p>No announcements matched the current filters. Adjust the filters or create the first announcement for your teams.</p>
            @if($canCreateAnnouncements)
                <button class="btn btn-primary" type="button" onclick="openAnnouncementModal()">
                    <i data-lucide="plus"></i> Create Announcement
                </button>
            @endif
        </div>
    </div>
@endif

@if($canCreateAnnouncements || $canEditAnnouncements)
    <div id="announcementModal" class="modal-overlay" style="display: none;">
        <div class="modal-container announcement-modal-container">
            <div class="modal-header announcement-modal-header">
                <div>
                    <h2 id="announcementModalTitle">New Announcement</h2>
                    <p class="modal-desc" style="margin-bottom: 0;">Choose the announcement type first, then define audience and timing.</p>
                </div>
                <button class="close-btn announcement-close-btn" type="button" onclick="closeModal('announcementModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body announcement-modal-body">
                <form id="announcementForm" class="modal-form">
                    @csrf
                    <input type="hidden" name="announcement_id" id="announcement_id">
                    <div class="announcement-form-top">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" id="announcement_title" required maxlength="255" placeholder="Enter announcement title">
                        </div>

                        <div class="form-group">
                            <label>Announcement Message</label>
                            <textarea name="message" id="announcement_message" rows="6" required maxlength="5000" placeholder="Write the full announcement here..."></textarea>
                        </div>
                    </div>

                    <div class="announcement-form-grid">
                        <div class="announcement-form-main">
                            <div class="announcement-side-card">
                                <div class="section-header compact">
                                    <div>
                                        <h2>Announcement Type</h2>
                                        <p>Select what kind of announcement you are publishing.</p>
                                    </div>
                                </div>
                                <div class="type-selector-grid">
                                    @foreach($announcementTypes as $typeKey => $typeLabel)
                                        <label class="type-option-card" data-type-option="{{ $typeKey }}">
                                            <input type="radio" name="announcement_type" value="{{ $typeKey }}" @checked($loop->first)>
                                            <span class="type-option-copy">
                                                <strong>{{ $typeLabel }}</strong>
                                                <small>
                                                    @if($typeKey === \App\Models\Announcement::TYPE_OFFICIAL_HOLIDAY)
                                                        Use for office closures, official holidays, and multi-day breaks.
                                                    @else
                                                        Use for general team updates, reminders, or department communication.
                                                    @endif
                                                </small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div id="holidayAnnouncementFields" class="holiday-config-card" style="display: none;">
                                <div class="section-header compact" style="margin-bottom: 14px;">
                                    <div>
                                        <h2>Holiday Dates</h2>
                                        <p>Select either a single holiday date or a date range for longer office closure periods.</p>
                                    </div>
                                </div>
                                <div class="holiday-date-mode-grid">
                                    @foreach($announcementDateModes as $modeKey => $modeLabel)
                                        <label class="type-option-card compact" data-date-mode-option="{{ $modeKey }}">
                                            <input type="radio" name="date_mode" value="{{ $modeKey }}" @checked($loop->first)>
                                            <span class="type-option-copy">
                                                <strong>{{ $modeLabel }}</strong>
                                                <small>
                                                    @if($modeKey === \App\Models\Announcement::DATE_MODE_SINGLE)
                                                        One official holiday date.
                                                    @else
                                                        A start and end date for a continuous holiday period.
                                                    @endif
                                                </small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <div id="singleDateField" class="holiday-date-fields">
                                    <div class="form-group" style="margin: 0;">
                                        <label>Holiday Date</label>
                                        <input type="date" name="event_date" id="announcement_event_date">
                                    </div>
                                </div>
                                <div id="rangeDateFields" class="holiday-date-fields holiday-date-range" style="display: none;">
                                    <div class="form-group" style="margin: 0;">
                                        <label>Start Date</label>
                                        <input type="date" name="event_start_date" id="announcement_event_start_date">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label>End Date</label>
                                        <input type="date" name="event_end_date" id="announcement_event_end_date">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="announcement-form-side">
                            <div class="announcement-side-card">
                                <div class="section-header compact">
                                    <div>
                                        <h2>Audience & Visibility</h2>
                                        <p>Control who can see this announcement once it is published.</p>
                                    </div>
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

                                <div class="form-group" id="announcementDepartmentGroup" style="margin-top: 18px;">
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
                            </div>
                        </div>
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

        function selectedAnnouncementType() {
            return document.querySelector('input[name="announcement_type"]:checked')?.value || '{{ \App\Models\Announcement::TYPE_GENERAL }}';
        }

        function selectedHolidayDateMode() {
            return document.querySelector('input[name="date_mode"]:checked')?.value || '{{ \App\Models\Announcement::DATE_MODE_SINGLE }}';
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

        function syncAnnouncementTypeState() {
            const type = selectedAnnouncementType();
            const holidayFields = document.getElementById('holidayAnnouncementFields');
            const isHoliday = type === '{{ \App\Models\Announcement::TYPE_OFFICIAL_HOLIDAY }}';

            holidayFields.style.display = isHoliday ? 'block' : 'none';

            document.querySelectorAll('[data-type-option]').forEach((card) => {
                card.classList.toggle('selected', card.dataset.typeOption === type);
            });

            if (!isHoliday) {
                document.getElementById('announcement_event_date').value = '';
                document.getElementById('announcement_event_start_date').value = '';
                document.getElementById('announcement_event_end_date').value = '';
            }

            syncHolidayDateModeState();
        }

        function syncHolidayDateModeState() {
            const isHoliday = selectedAnnouncementType() === '{{ \App\Models\Announcement::TYPE_OFFICIAL_HOLIDAY }}';
            const mode = selectedHolidayDateMode();
            const singleField = document.getElementById('singleDateField');
            const rangeFields = document.getElementById('rangeDateFields');

            document.querySelectorAll('[data-date-mode-option]').forEach((card) => {
                card.classList.toggle('selected', card.dataset.dateModeOption === mode);
            });

            if (!isHoliday) {
                singleField.style.display = 'none';
                rangeFields.style.display = 'none';
                return;
            }

            singleField.style.display = mode === '{{ \App\Models\Announcement::DATE_MODE_SINGLE }}' ? 'grid' : 'none';
            rangeFields.style.display = mode === '{{ \App\Models\Announcement::DATE_MODE_RANGE }}' ? 'grid' : 'none';
        }

        function openAnnouncementModal(announcement = null) {
            const form = document.getElementById('announcementForm');
            form.reset();

            document.getElementById('announcement_id').value = announcement?.id || '';
            document.getElementById('announcement_title').value = announcement?.title || '';
            document.getElementById('announcement_message').value = announcement?.message || '';
            document.getElementById('announcement_is_global').checked = Boolean(announcement?.is_global);
            document.getElementById('announcement_is_active').checked = announcement ? Boolean(announcement.is_active) : true;
            document.getElementById('announcement_event_date').value = announcement?.event_date || '';
            document.getElementById('announcement_event_start_date').value = announcement?.event_start_date || '';
            document.getElementById('announcement_event_end_date').value = announcement?.event_end_date || '';

            const type = announcement?.announcement_type || '{{ \App\Models\Announcement::TYPE_GENERAL }}';
            const dateMode = announcement?.date_mode || '{{ \App\Models\Announcement::DATE_MODE_SINGLE }}';

            form.querySelectorAll('input[name="announcement_type"]').forEach((radio) => {
                radio.checked = radio.value === type;
            });

            form.querySelectorAll('input[name="date_mode"]').forEach((radio) => {
                radio.checked = radio.value === dateMode;
            });

            form.querySelectorAll('input[name="department_ids[]"]').forEach((checkbox) => {
                checkbox.checked = Boolean(announcement?.department_ids?.includes(Number(checkbox.value)));
            });

            document.getElementById('announcementModalTitle').textContent = announcement ? 'Edit Announcement' : 'New Announcement';
            syncAnnouncementDepartmentState();
            syncAnnouncementTypeState();
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
        document.querySelectorAll('input[name="announcement_type"]').forEach((radio) => {
            radio.addEventListener('change', syncAnnouncementTypeState);
        });
        document.querySelectorAll('input[name="date_mode"]').forEach((radio) => {
            radio.addEventListener('change', syncHolidayDateModeState);
        });

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
        grid-template-columns: minmax(0, 1.5fr) repeat(3, minmax(180px, 0.7fr)) auto;
        gap: 16px;
        align-items: end;
    }
    .announcement-filter-actions {
        display: flex;
        justify-content: flex-end;
        align-items: flex-end;
    }
    .announcement-clear-btn {
        white-space: nowrap;
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
    .announcement-main-copy h2 {
        margin: 0 0 8px;
        font-size: 24px;
        color: #111827;
    }
    .announcement-meta-row {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    .announcement-meta-text {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }
    .type-chip {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid transparent;
    }
    .type-chip.general {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .type-chip.holiday {
        background: #fff7ed;
        border-color: #fdba74;
        color: #c2410c;
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
    .announcement-modal-container {
        max-width: 1040px;
        width: min(1040px, calc(100vw - 32px));
        border-radius: 22px;
        overflow: hidden;
    }
    .announcement-modal-header {
        padding: 22px 24px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
    }
    .announcement-modal-body {
        padding: 24px;
        background: #f8fafc;
        max-height: calc(100vh - 190px);
        overflow-y: auto;
    }
    .announcement-close-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        color: #111827;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease, border-color 0.2s ease;
    }
    .announcement-close-btn:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    .announcement-form-top {
        display: grid;
        gap: 18px;
        margin-bottom: 18px;
    }
    .announcement-form-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
        gap: 20px;
        align-items: start;
    }
    .announcement-form-main,
    .announcement-form-side {
        display: grid;
        gap: 18px;
    }
    .announcement-side-card,
    .holiday-config-card {
        padding: 18px;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }
    .section-header.compact {
        margin-bottom: 12px;
    }
    .section-header.compact h2 {
        font-size: 18px;
        margin-bottom: 4px;
    }
    .section-header.compact p {
        font-size: 13px;
    }
    .announcement-side-card h3 {
        margin: 0 0 14px;
        font-size: 18px;
        color: #111827;
    }
    .type-selector-grid,
    .holiday-date-mode-grid {
        display: grid;
        gap: 12px;
    }
    .type-option-card {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 14px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
        cursor: pointer;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }
    .type-option-card.compact {
        padding: 12px 14px;
    }
    .type-option-card.selected {
        border-color: #fdba74;
        background: #fff7ed;
        box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.12);
    }
    .type-option-card input[type="radio"] {
        margin-top: 2px;
        flex-shrink: 0;
    }
    .type-option-copy {
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex: 1;
    }
    .type-option-copy strong {
        font-size: 14px;
        color: #111827;
    }
    .type-option-copy small {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.55;
    }
    .announcement-toggle-grid {
        display: grid;
        gap: 12px;
    }
    .checkbox-card {
        padding: 14px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fcfcfd;
        align-items: flex-start;
        min-height: 88px;
    }
    .checkbox-card input[type="checkbox"] {
        margin-top: 3px;
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
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        padding: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
    }
    .department-choice {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f9fafb;
        min-height: 52px;
    }
    .holiday-date-fields {
        display: grid;
        gap: 14px;
        margin-top: 16px;
    }
    .holiday-date-range {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .modal-form textarea {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        resize: vertical;
        min-height: 160px;
    }
    .announcement-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 14px;
        min-height: 260px;
        padding: 36px 28px;
    }
    .announcement-empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 22px;
        background: linear-gradient(135deg, #ffe4cf 0%, #fff2e7 100%);
        color: #f97316;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .announcement-empty-icon i {
        width: 32px;
        height: 32px;
    }
    .announcement-empty-state h3 {
        margin: 0;
        font-size: 24px;
        color: #111827;
    }
    .announcement-empty-state p {
        margin: 0;
        max-width: 560px;
        color: #6b7280;
        line-height: 1.7;
        font-size: 14px;
    }
    @media (max-width: 1180px) {
        .announcement-form-grid {
            grid-template-columns: 1fr;
        }
        .announcement-filter-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 900px) {
        .announcement-filter-form,
        .holiday-date-range {
            grid-template-columns: 1fr;
        }
        .announcement-filter-actions {
            justify-content: stretch;
        }
        .announcement-filter-actions .btn {
            width: 100%;
        }
        .announcement-card-header {
            flex-direction: column;
        }
    }
</style>
@endsection
