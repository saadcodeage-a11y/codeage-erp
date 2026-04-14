@extends('layouts.app')

@section('title', 'Reports')

@php
    $currentDepartment = $report['filters']['department_id'] ?? null;
    $currentSearch = $report['filters']['search'] ?? '';
    $selectedDepartment = $departments->firstWhere('id', (int) $currentDepartment);
    $selectedEmployee = $employees->first(function ($employee) use ($currentSearch) {
        if (! $currentSearch) {
            return false;
        }

        return strcasecmp((string) $employee->employee_id, (string) $currentSearch) === 0
            || strcasecmp((string) $employee->full_name, (string) $currentSearch) === 0
            || str_contains(strtolower($employee->full_name . ' ' . $employee->employee_id), strtolower((string) $currentSearch));
    });
@endphp

@section('content')
<div class="page-header reports-header">
    <div class="header-left">
        <div class="reports-title-row">
            <div class="reports-title-icon">
                <i data-lucide="files"></i>
            </div>
            <div>
                <h1>Reports</h1>
                <p>Tax, attendance, payroll, and performance reporting with export-ready summaries.</p>
            </div>
        </div>
    </div>
    <div class="header-right reports-header-actions">
        <a href="{{ route('reports.csv', ['reportType' => $activeTab] + request()->query()) }}" class="btn btn-outline">
            <i data-lucide="file-spreadsheet"></i> Export CSV
        </a>
        <a href="{{ route('reports.pdf', ['reportType' => $activeTab] + request()->query()) }}" class="btn btn-primary">
            <i data-lucide="file-down"></i> Export PDF
        </a>
    </div>
</div>

<div class="reports-top-card">
    <div class="tabs-container reports-tabs">
        @foreach($tabs as $key => $label)
            <a href="{{ route('reports.index', array_merge(request()->query(), ['tab' => $key])) }}" class="tab-item {{ $activeTab === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="reports-hero-grid">
        <div class="reports-hero-copy">
            <span class="reports-eyebrow">{{ $report['title'] }}</span>
            <h2>{{ $report['description'] }}</h2>
            <p>Use the filters below to generate printable summaries or export raw data without leaving this module.</p>
        </div>
        <div class="reports-hero-export">
            <div class="reports-export-card">
                <span>Current Report</span>
                <strong>{{ $tabs[$activeTab] }}</strong>
                <small>Exports use the current filter selection.</small>
            </div>
        </div>
    </div>
</div>

<div class="stats-grid reports-stats-grid">
    @foreach(($report['summary_cards'] ?? []) as $card)
        <div class="stat-card reports-stat-card">
            <div class="stat-content">
                <span class="stat-label">{{ $card['label'] }}</span>
                <span class="stat-value">{{ $card['value'] }}</span>
            </div>
        </div>
    @endforeach
</div>

@if(! empty($report['secondary_summary_cards']))
    <div class="stats-grid reports-stats-grid secondary-stats-grid">
        @foreach($report['secondary_summary_cards'] as $card)
            <div class="stat-card reports-stat-card secondary">
                <div class="stat-content">
                    <span class="stat-label">{{ $card['label'] }}</span>
                    <span class="stat-value">{{ $card['value'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="table-card reports-filter-card">
    <div class="table-card-header">
        <div>
            <h2>Report Filters</h2>
            <p>Search with guided dropdowns and apply the same selection to on-screen summaries and exports.</p>
        </div>
    </div>

    <form action="{{ route('reports.index') }}" method="GET" class="report-filter-form" id="reportsFilterForm">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <input type="hidden" name="search" id="report_search_value" value="{{ $currentSearch }}">
        <input type="hidden" name="department_id" id="report_department_id" value="{{ $currentDepartment }}">

        <div class="filter-field wide">
            <label>Employee</label>
            <div class="search-select" data-search-select>
                <div class="search-select-control">
                    <i data-lucide="search"></i>
                    <input
                        type="text"
                        id="report_search_display"
                        placeholder="Search employee by name, ID, or designation"
                        value="{{ $selectedEmployee ? ($selectedEmployee->employee_id ? $selectedEmployee->employee_id . ' - ' . $selectedEmployee->full_name : $selectedEmployee->full_name) : $currentSearch }}"
                        data-search-select-input
                        data-target-input="report_search_value"
                    >
                    <button type="button" class="search-select-toggle" data-search-select-toggle aria-label="Toggle employee search">
                        <i data-lucide="chevron-down"></i>
                    </button>
                </div>
                <div class="search-select-dropdown" data-search-select-dropdown>
                    <button type="button" class="search-select-option reset" data-search-reset data-target-input="report_search_value" data-target-display="report_search_display">
                        <div>
                            <strong>All employees</strong>
                            <span>Clear employee filter</span>
                        </div>
                    </button>
                    @foreach($employees as $employee)
                        @php
                            $employeeLabel = $employee->employee_id
                                ? $employee->employee_id . ' - ' . $employee->full_name
                                : $employee->full_name;
                            $employeeSearchText = strtolower(trim(implode(' ', array_filter([
                                $employee->employee_id,
                                $employee->full_name,
                                $employee->designation,
                                $employee->department?->name,
                            ]))));
                            $employeeValue = $employee->employee_id ?: $employee->full_name;
                        @endphp
                        <button
                            type="button"
                            class="search-select-option"
                            data-search-option
                            data-search-text="{{ $employeeSearchText }}"
                            data-value="{{ $employeeValue }}"
                            data-display="{{ $employeeLabel }}"
                            data-target-input="report_search_value"
                            data-target-display="report_search_display"
                        >
                            <div>
                                <strong>{{ $employeeLabel }}</strong>
                                <span>{{ $employee->designation ?: 'No designation' }}{{ $employee->department ? ' | ' . $employee->department->name : '' }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="filter-field">
            <label>Department</label>
            <div class="search-select" data-search-select>
                <div class="search-select-control">
                    <i data-lucide="building-2"></i>
                    <input
                        type="text"
                        id="report_department_display"
                        placeholder="Search department"
                        value="{{ $selectedDepartment?->name }}"
                        data-search-select-input
                        data-target-input="report_department_id"
                    >
                    <button type="button" class="search-select-toggle" data-search-select-toggle aria-label="Toggle department search">
                        <i data-lucide="chevron-down"></i>
                    </button>
                </div>
                <div class="search-select-dropdown" data-search-select-dropdown>
                    <button type="button" class="search-select-option reset" data-search-reset data-target-input="report_department_id" data-target-display="report_department_display">
                        <div>
                            <strong>All departments</strong>
                            <span>Clear department filter</span>
                        </div>
                    </button>
                    @foreach($departments as $department)
                        <button
                            type="button"
                            class="search-select-option"
                            data-search-option
                            data-search-text="{{ strtolower($department->name) }}"
                            data-value="{{ $department->id }}"
                            data-display="{{ $department->name }}"
                            data-target-input="report_department_id"
                            data-target-display="report_department_display"
                        >
                            <div>
                                <strong>{{ $department->name }}</strong>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        @if($activeTab === 'tax')
            <div class="filter-field">
                <label>Fiscal Year</label>
                <div class="select-wrapper">
                    <select name="fiscal_year">
                        @foreach($report['options']['fiscal_years'] as $year => $label)
                            <option value="{{ $year }}" @selected((int) $report['filters']['fiscal_year'] === (int) $year)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @elseif($activeTab === 'attendance')
            <div class="filter-field">
                <label>Month</label>
                <input type="month" name="month" value="{{ $report['filters']['month'] }}">
            </div>
            <div class="filter-field">
                <label>Status</label>
                <div class="select-wrapper">
                    <select name="status">
                        @foreach($report['options']['attendance_statuses'] as $value => $label)
                            <option value="{{ $value }}" @selected($report['filters']['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @elseif($activeTab === 'payroll')
            <div class="filter-field">
                <label>Payroll Month</label>
                <input type="month" name="month" value="{{ $report['filters']['month'] }}">
            </div>
            <div class="filter-field">
                <label>Fiscal Year</label>
                <div class="select-wrapper">
                    <select name="fiscal_year">
                        @foreach($report['options']['fiscal_years'] as $year => $label)
                            <option value="{{ $year }}" @selected((int) $report['filters']['fiscal_year'] === (int) $year)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="filter-field">
                <label>Payout Status</label>
                <div class="select-wrapper">
                    <select name="payout_status">
                        @foreach($report['options']['payout_statuses'] as $value => $label)
                            <option value="{{ $value }}" @selected($report['filters']['payout_status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <div class="filter-field">
                <label>Evaluation Type</label>
                <div class="select-wrapper">
                    <select name="type">
                        @foreach($report['options']['types'] as $value => $label)
                            <option value="{{ $value }}" @selected($report['filters']['type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="filter-field">
                <label>Status</label>
                <div class="select-wrapper">
                    <select name="status">
                        @foreach($report['options']['statuses'] as $value => $label)
                            <option value="{{ $value }}" @selected($report['filters']['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="filter-field">
                <label>Period Start</label>
                <input type="date" name="start_date" value="{{ $report['filters']['start_date'] }}">
            </div>
            <div class="filter-field">
                <label>Period End</label>
                <input type="date" name="end_date" value="{{ $report['filters']['end_date'] }}">
            </div>
        @endif

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="filter"></i> Apply Filters
            </button>
            <a href="{{ route('reports.index', ['tab' => $activeTab]) }}" class="btn btn-outline">Clear</a>
        </div>
    </form>
</div>

@if(! empty($report['table']))
    <div class="table-card reports-section-card">
        <div class="table-card-header reports-section-header">
            <div>
                <h2>{{ $report['table']['title'] }}</h2>
                <p>{{ $report['table']['description'] }}</p>
            </div>
            <span class="reports-count-pill">{{ count($report['table']['rows']) }} rows</span>
        </div>
        <div class="table-scroll">
            <table class="data-table reports-table">
                <thead>
                    <tr>
                        @foreach($report['table']['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['table']['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td class="preline">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($report['table']['columns']) }}" class="text-center">No report rows found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@foreach(($report['sections'] ?? []) as $section)
    <div class="table-card reports-section-card">
        <div class="table-card-header reports-section-header">
            <div>
                <h2>{{ $section['title'] }}</h2>
                <p>{{ $section['description'] }}</p>
            </div>
            <span class="reports-count-pill">{{ count($section['rows']) }} rows</span>
        </div>
        <div class="table-scroll">
            <table class="data-table reports-table">
                <thead>
                    <tr>
                        @foreach($section['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($section['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td class="preline">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($section['columns']) }}" class="text-center">No data available for this section.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach

<style>
    .reports-header {
        margin-bottom: 1rem;
    }

    .reports-title-row {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .reports-title-icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        color: #f97316;
        border: 1px solid #fed7aa;
        flex-shrink: 0;
    }

    .reports-title-icon i {
        width: 24px;
        height: 24px;
    }

    .reports-header-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .reports-top-card {
        background: linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
        border: 1px solid #f3e8d7;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .reports-tabs {
        margin-bottom: 1.5rem;
        overflow-x: auto;
    }

    .reports-hero-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(280px, 0.8fr);
        gap: 1rem;
        align-items: stretch;
    }

    .reports-eyebrow {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #c2410c;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.75rem;
    }

    .reports-hero-copy h2 {
        font-size: 1.35rem;
        line-height: 1.35;
        color: #111827;
        margin: 0 0 0.5rem;
    }

    .reports-hero-copy p {
        margin: 0;
        color: #6b7280;
        max-width: 720px;
    }

    .reports-export-card {
        height: 100%;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .reports-export-card span {
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
        margin-bottom: 0.4rem;
    }

    .reports-export-card strong {
        font-size: 1.05rem;
        color: #111827;
        margin-bottom: 0.4rem;
    }

    .reports-export-card small {
        color: #6b7280;
        line-height: 1.5;
    }

    .reports-stats-grid {
        margin-bottom: 1.25rem;
    }

    .reports-stat-card {
        min-height: 132px;
    }

    .reports-stat-card.secondary {
        background: #fffaf3;
        border-color: #fed7aa;
    }

    .secondary-stats-grid {
        margin-top: -0.25rem;
    }

    .reports-filter-card,
    .reports-section-card {
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .reports-filter-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
    }

    .reports-section-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
    }

    .table-card-header h2 {
        font-size: 1.125rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.35rem;
    }

    .table-card-header p {
        margin: 0;
        color: #6b7280;
    }

    .reports-section-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .reports-count-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.45rem 0.85rem;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475467;
        font-size: 0.82rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .report-filter-form {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1.25rem;
        align-items: end;
    }

    .filter-field {
        grid-column: span 3;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-field.wide {
        grid-column: span 6;
    }

    .filter-field label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #374151;
    }

    .filter-field input,
    .filter-field select {
        width: 100%;
        padding: 0.9rem 1rem;
        border: 1px solid #dbe2ea;
        border-radius: 14px;
        background: #fff;
        color: #111827;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .filter-field input:focus,
    .filter-field select:focus {
        border-color: #fb923c;
        box-shadow: 0 0 0 4px rgba(251, 146, 60, 0.12);
    }

    .select-wrapper {
        position: relative;
    }

    .select-wrapper::after {
        content: '';
        position: absolute;
        top: 50%;
        right: 16px;
        width: 9px;
        height: 9px;
        border-right: 2px solid #6b7280;
        border-bottom: 2px solid #6b7280;
        transform: translateY(-65%) rotate(45deg);
        pointer-events: none;
    }

    .select-wrapper select {
        appearance: none;
        padding-right: 2.75rem;
    }

    .search-select {
        position: relative;
    }

    .search-select-control {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border: 1px solid #dbe2ea;
        border-radius: 14px;
        background: #fff;
        padding: 0 1rem;
        min-height: 51px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .search-select.open .search-select-control,
    .search-select-control:focus-within {
        border-color: #fb923c;
        box-shadow: 0 0 0 4px rgba(251, 146, 60, 0.12);
    }

    .search-select-control > i {
        width: 18px;
        height: 18px;
        color: #94a3b8;
        flex-shrink: 0;
    }

    .search-select-control input {
        border: none;
        box-shadow: none;
        background: transparent;
        padding: 0;
        min-height: auto;
    }

    .search-select-control input:focus {
        box-shadow: none;
        border-color: transparent;
    }

    .search-select-toggle {
        border: none;
        background: transparent;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        cursor: pointer;
        flex-shrink: 0;
    }

    .search-select-toggle i {
        width: 18px;
        height: 18px;
        transition: transform 0.2s ease;
    }

    .search-select.open .search-select-toggle i {
        transform: rotate(180deg);
    }

    .search-select-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        padding: 8px;
        max-height: 290px;
        overflow: auto;
        z-index: 30;
        display: none;
    }

    .search-select.open .search-select-dropdown {
        display: block;
    }

    .search-select-option {
        width: 100%;
        border: none;
        background: #fff;
        border-radius: 12px;
        padding: 12px 14px;
        text-align: left;
        cursor: pointer;
        transition: background 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .search-select-option:hover,
    .search-select-option.active {
        background: #fff7ed;
    }

    .search-select-option strong {
        display: block;
        font-size: 13px;
        color: #111827;
        margin-bottom: 2px;
    }

    .search-select-option span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }

    .search-select-option.reset strong {
        color: #c2410c;
    }

    .filter-actions {
        grid-column: span 12;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        padding-top: 0.25rem;
    }

    .table-scroll {
        overflow-x: auto;
    }

    .reports-table td.preline {
        white-space: pre-line;
    }

    .reports-table thead th {
        position: sticky;
        top: 0;
        background: #f8fafc;
        z-index: 1;
    }

    @media (max-width: 1200px) {
        .reports-hero-grid {
            grid-template-columns: 1fr;
        }

        .filter-field,
        .filter-field.wide {
            grid-column: span 6;
        }
    }

    @media (max-width: 768px) {
        .reports-header-actions {
            width: 100%;
        }

        .reports-header-actions .btn {
            flex: 1 1 auto;
            justify-content: center;
        }

        .filter-field,
        .filter-field.wide,
        .filter-actions {
            grid-column: span 12;
        }

        .reports-section-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selects = document.querySelectorAll('[data-search-select]');

        function closeAllSearchSelects(except = null) {
            selects.forEach((root) => {
                if (root !== except) {
                    root.classList.remove('open');
                }
            });
        }

        selects.forEach((root) => {
            const input = root.querySelector('[data-search-select-input]');
            const toggle = root.querySelector('[data-search-select-toggle]');
            const dropdown = root.querySelector('[data-search-select-dropdown]');
            const hiddenInputId = input?.dataset.targetInput;
            const hiddenInput = hiddenInputId ? document.getElementById(hiddenInputId) : null;
            const options = Array.from(root.querySelectorAll('[data-search-option]'));
            const resets = Array.from(root.querySelectorAll('[data-search-reset]'));

            function filterOptions() {
                const query = (input?.value || '').trim().toLowerCase();
                let visibleCount = 0;

                options.forEach((option) => {
                    const matches = !query || (option.dataset.searchText || '').includes(query);
                    option.style.display = matches ? 'flex' : 'none';
                    if (matches) {
                        visibleCount++;
                    }
                });

                resets.forEach((reset) => {
                    reset.style.display = !query || visibleCount === 0 ? 'flex' : 'flex';
                });
            }

            function openDropdown() {
                closeAllSearchSelects(root);
                root.classList.add('open');
                filterOptions();
            }

            function closeDropdown() {
                root.classList.remove('open');
            }

            toggle?.addEventListener('click', () => {
                if (root.classList.contains('open')) {
                    closeDropdown();
                } else {
                    openDropdown();
                    input?.focus();
                }
            });

            input?.addEventListener('focus', () => {
                openDropdown();
                if (hiddenInput && hiddenInput.id === 'report_department_id') {
                    hiddenInput.value = hiddenInput.value;
                }
            });

            input?.addEventListener('input', () => {
                filterOptions();

                if (hiddenInput?.id === 'report_search_value') {
                    hiddenInput.value = input.value.trim();
                } else if (hiddenInput) {
                    hiddenInput.value = '';
                }
            });

            options.forEach((option) => {
                option.addEventListener('click', () => {
                    const display = option.dataset.display || option.dataset.value || '';
                    const value = option.dataset.value || '';

                    if (input) {
                        input.value = display;
                    }

                    if (hiddenInput) {
                        hiddenInput.value = value;
                    }

                    closeDropdown();
                });
            });

            resets.forEach((reset) => {
                reset.addEventListener('click', () => {
                    const targetInput = document.getElementById(reset.dataset.targetInput || '');
                    const targetDisplay = document.getElementById(reset.dataset.targetDisplay || '');

                    if (targetInput) {
                        targetInput.value = '';
                    }

                    if (targetDisplay) {
                        targetDisplay.value = '';
                    }

                    filterOptions();
                    closeDropdown();
                });
            });
        });

        document.addEventListener('click', (event) => {
            if (! event.target.closest('[data-search-select]')) {
                closeAllSearchSelects();
            }
        });
    });
</script>
@endsection
