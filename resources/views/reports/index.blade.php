@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>Reports</h1>
        <p>Tax reports, attendance reports, payroll summaries, and performance analytics in one place.</p>
    </div>
    <div class="header-right">
        <a href="{{ route('reports.csv', ['reportType' => $activeTab] + request()->query()) }}" class="btn btn-outline">
            <i data-lucide="file-spreadsheet"></i> Export CSV
        </a>
        <a href="{{ route('reports.pdf', ['reportType' => $activeTab] + request()->query()) }}" class="btn btn-primary">
            <i data-lucide="file-down"></i> Export PDF
        </a>
    </div>
</div>

<div class="tabs-container reports-tabs">
    @foreach($tabs as $key => $label)
        <a href="{{ route('reports.index', array_merge(request()->query(), ['tab' => $key])) }}" class="tab-item {{ $activeTab === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="stats-grid">
    @foreach(($report['summary_cards'] ?? []) as $card)
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">{{ $card['label'] }}</span>
                <span class="stat-value">{{ $card['value'] }}</span>
            </div>
        </div>
    @endforeach
</div>

@if(! empty($report['secondary_summary_cards']))
    <div class="stats-grid secondary-stats-grid">
        @foreach($report['secondary_summary_cards'] as $card)
            <div class="stat-card secondary">
                <div class="stat-content">
                    <span class="stat-label">{{ $card['label'] }}</span>
                    <span class="stat-value">{{ $card['value'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="search-container report-filter-card">
    <div class="report-filter-header">
        <div>
            <h2>{{ $report['title'] }}</h2>
            <p>{{ $report['description'] }}</p>
        </div>
    </div>

    <form action="{{ route('reports.index') }}" method="GET" class="report-filter-form">
        <input type="hidden" name="tab" value="{{ $activeTab }}">

        @if($activeTab === 'tax')
            <div class="filter-field wide">
                <label>Search Employee</label>
                <input type="text" name="search" value="{{ $report['filters']['search'] }}" placeholder="Search by employee name, ID, or designation">
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
                <label>Department</label>
                <div class="select-wrapper">
                    <select name="department_id">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) $report['filters']['department_id'] === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @elseif($activeTab === 'attendance')
            <div class="filter-field">
                <label>Month</label>
                <input type="month" name="month" value="{{ $report['filters']['month'] }}">
            </div>
            <div class="filter-field wide">
                <label>Search Employee</label>
                <input type="text" name="search" value="{{ $report['filters']['search'] }}" placeholder="Search by employee name, ID, or designation">
            </div>
            <div class="filter-field">
                <label>Department</label>
                <div class="select-wrapper">
                    <select name="department_id">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) $report['filters']['department_id'] === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
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
            <div class="filter-field wide">
                <label>Search Employee</label>
                <input type="text" name="search" value="{{ $report['filters']['search'] }}" placeholder="Search by employee name, ID, or designation">
            </div>
            <div class="filter-field">
                <label>Department</label>
                <div class="select-wrapper">
                    <select name="department_id">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) $report['filters']['department_id'] === $department->id)>{{ $department->name }}</option>
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
            <div class="filter-field wide">
                <label>Search Employee</label>
                <input type="text" name="search" value="{{ $report['filters']['search'] }}" placeholder="Search by employee name, ID, or designation">
            </div>
            <div class="filter-field">
                <label>Department</label>
                <div class="select-wrapper">
                    <select name="department_id">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected((int) $report['filters']['department_id'] === $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
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
    <div class="table-card">
        <div class="table-card-header">
            <div>
                <h2>{{ $report['table']['title'] }}</h2>
                <p>{{ $report['table']['description'] }}</p>
            </div>
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
    <div class="table-card">
        <div class="table-card-header">
            <div>
                <h2>{{ $section['title'] }}</h2>
                <p>{{ $section['description'] }}</p>
            </div>
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
    .reports-tabs {
        margin-bottom: 1.5rem;
        overflow-x: auto;
    }

    .secondary-stats-grid {
        margin-top: -0.5rem;
        margin-bottom: 1.5rem;
    }

    .stat-card.secondary {
        background: #fffaf3;
        border-color: #fed7aa;
    }

    .report-filter-card {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .report-filter-header h2,
    .table-card-header h2 {
        font-size: 1.125rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.25rem;
    }

    .report-filter-header p,
    .table-card-header p {
        color: #6b7280;
        margin: 0;
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
        padding: 0.85rem 1rem;
        border: 1px solid #dbe2ea;
        border-radius: 14px;
        background: #fff;
        color: #111827;
        outline: none;
    }

    .select-wrapper {
        position: relative;
    }

    .filter-actions {
        grid-column: span 12;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
    }

    .table-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    }

    .table-scroll {
        overflow-x: auto;
    }

    .reports-table td.preline {
        white-space: pre-line;
    }

    @media (max-width: 1200px) {
        .filter-field,
        .filter-field.wide {
            grid-column: span 6;
        }
    }

    @media (max-width: 768px) {
        .filter-field,
        .filter-field.wide,
        .filter-actions {
            grid-column: span 12;
        }

        .header-right {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
    }
</style>
@endsection
