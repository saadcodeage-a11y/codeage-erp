@extends('layouts.app')

@section('title', 'Payroll')

@section('content')
@php
    $canGeneratePayroll = Auth::user()->canAccessModule('payroll_management', 'create');
    $canEditPayroll = Auth::user()->canAccessModule('payroll_management', 'edit');
    $selectedMonthLabel = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('F Y');
    $selectedRunTotals = $selectedRun
        ? [
            'gross_salary' => round($selectedRun->records->sum('gross_salary'), 2),
            'income_tax' => round($selectedRun->records->sum('income_tax'), 2),
            'net_salary' => round($selectedRun->records->sum('net_salary'), 2),
        ]
        : null;
@endphp

<div class="page-header payroll-page-header">
    <div class="header-left">
        <h1>Payroll Payouts</h1>
        <p>Review past payout months, open month details, and create a new payout pack with payslips and bank transfer files.</p>
    </div>
    @if($canGeneratePayroll)
        <button type="button" class="btn btn-primary" onclick="openPayoutModal()">
            <i data-lucide="wallet-cards"></i> Create Payouts
        </button>
    @endif
</div>

@if(session('success') || $errors->any())
    <div class="attendance-feedback-stack" style="margin-bottom: 24px;">
        @if(session('success'))
            <div class="status-banner success">
                <i data-lucide="circle-check-big"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="status-banner danger">
                <i data-lucide="octagon-alert"></i>
                <div>
                    <strong>Payroll action could not be completed.</strong>
                    <p>{{ $errors->first() }}</p>
                </div>
            </div>
        @endif
    </div>
@endif

<div class="payroll-overview-stats">
    <div class="stat-card compact">
        <div class="stat-content">
            <span class="stat-label">Preview Month</span>
            <span class="stat-value">{{ $selectedMonthLabel }}</span>
        </div>
    </div>
    <div class="stat-card compact">
        <div class="stat-content">
            <span class="stat-label">Projected Gross</span>
            <span class="stat-value">PKR {{ number_format($totals['gross_salary'], 2) }}</span>
        </div>
    </div>
    <div class="stat-card compact">
        <div class="stat-content">
            <span class="stat-label">Projected Tax</span>
            <span class="stat-value">PKR {{ number_format($totals['income_tax'], 2) }}</span>
        </div>
    </div>
    <div class="stat-card compact">
        <div class="stat-content">
            <span class="stat-label">Projected Net</span>
            <span class="stat-value">PKR {{ number_format($totals['net_salary'], 2) }}</span>
        </div>
    </div>
</div>

<div class="payroll-workspace">
    <aside class="card payroll-history-panel">
        <div class="section-header" style="margin-bottom: 16px;">
            <div>
                <h2>Payout Months</h2>
                <p>Open any historical month to review payout details and outputs.</p>
            </div>
        </div>

        <div class="payroll-history-list">
            @forelse($runs as $run)
                <a href="{{ route('payroll.index', ['month' => $run->pay_period_month->format('Y-m'), 'run' => $run->id]) }}" class="payroll-history-item {{ $selectedRun?->id === $run->id ? 'active' : '' }}">
                    <div>
                        <strong>{{ $run->pay_period_month->format('F Y') }}</strong>
                        <p>{{ $run->records_count }} employees | {{ optional($run->payment_date)->format('d M, Y') ?? 'No payment date' }}</p>
                    </div>
                    <span class="history-status {{ $run->status }}">{{ ucfirst($run->status) }}</span>
                </a>
            @empty
                <div class="empty-state-panel small">
                    No payout months have been created yet.
                </div>
            @endforelse
        </div>
    </aside>

    <section class="payroll-detail-panel">
        @if($selectedRun)
            <div class="card">
                <div class="section-header" style="margin-bottom: 16px;">
                    <div>
                        <h2>{{ $selectedRun->pay_period_month->format('F Y') }} Details</h2>
                        <p>{{ $selectedRun->name }} | Generated {{ optional($selectedRun->generated_at)->format('d M, Y h:i A') ?? 'Not recorded' }}</p>
                    </div>
                    <span class="history-status {{ $selectedRun->status }}">{{ ucfirst($selectedRun->status) }}</span>
                </div>

                <div class="payroll-detail-metrics">
                    <div class="detail-metric">
                        <span>Employees</span>
                        <strong>{{ $selectedRun->records_count ?? $selectedRun->records->count() }}</strong>
                    </div>
                    <div class="detail-metric">
                        <span>Gross</span>
                        <strong>PKR {{ number_format($selectedRunTotals['gross_salary'], 2) }}</strong>
                    </div>
                    <div class="detail-metric">
                        <span>Tax</span>
                        <strong>PKR {{ number_format($selectedRunTotals['income_tax'], 2) }}</strong>
                    </div>
                    <div class="detail-metric">
                        <span>Net</span>
                        <strong>PKR {{ number_format($selectedRunTotals['net_salary'], 2) }}</strong>
                    </div>
                </div>

                <div class="payroll-action-row">
                    <a href="{{ route('payroll.payslips.zip.download', $selectedRun) }}" class="btn btn-primary">
                        <i data-lucide="file-archive"></i> Download Payslips ZIP
                    </a>
                    <a href="{{ route('payroll.ift.download', $selectedRun) }}" class="btn btn-outline">
                        <i data-lucide="sheet"></i> Download IFT
                        <span class="button-count">{{ $selectedRunExportCounts['ift'] }}</span>
                    </a>
                    <a href="{{ route('payroll.ibft.download', $selectedRun) }}" class="btn btn-outline">
                        <i data-lucide="sheet"></i> Download IBFT
                        <span class="button-count">{{ $selectedRunExportCounts['ibft'] }}</span>
                    </a>
                    @if($canGeneratePayroll && $selectedRun->status !== 'finalized')
                        <button type="button" class="btn btn-outline" onclick="openPayoutModal('{{ $selectedRun->pay_period_month->format('Y-m') }}', '{{ optional($selectedRun->payment_date)->toDateString() }}', @json($selectedRun->notes), true)">
                            <i data-lucide="square-pen"></i> Edit Payout
                        </button>
                    @endif
                    @if($canEditPayroll && $selectedRun->status !== 'finalized')
                        <form method="POST" action="{{ route('payroll.finalize', $selectedRun) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline">
                                <i data-lucide="badge-check"></i> Finalize Payout
                            </button>
                        </form>
                        <form method="POST" action="{{ route('payroll.destroy', $selectedRun) }}" onsubmit="return confirm('Delete this draft payout? This will remove all payout records for the month.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline danger-outline">
                                <i data-lucide="trash-2"></i> Delete Payout
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="section-header" style="margin-bottom: 16px;">
                    <div>
                        <h2>Payout Employees</h2>
                        <p>Month-level payout details, bank references, and downloadable payslips.</p>
                    </div>
                </div>

                <div class="payout-preview-tools">
                    <div class="payout-search-box">
                        <input type="search" placeholder="Search employee by name, ID, designation, bank, or account" data-saved-payout-search>
                    </div>
                    <span class="summary-pill muted" data-saved-payout-search-count>{{ $selectedRunRecords->total() }} employees</span>
                </div>

                <div class="payout-records-table-wrap">
                    <table class="payout-records-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Bank / Account</th>
                                <th>Absent</th>
                                <th>Short</th>
                                <th>Security</th>
                                <th>Gross</th>
                                <th>Tax</th>
                                <th>Net</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedRunRecords as $record)
                                @php
                                    $savedBankLabel = $record->employee->bank?->name ?? ($record->bank_code ?: 'No linked bank');
                                    $savedAccountLabel = $record->beneficiary_account_no ?: 'No account number';
                                    $savedSearchText = collect([
                                        $record->employee->full_name,
                                        $record->employee->employee_id,
                                        $record->employee->designation,
                                        $savedBankLabel,
                                        $savedAccountLabel,
                                    ])->filter()->implode(' ');
                                @endphp
                                <tr data-saved-payout-row data-search-text="{{ strtolower($savedSearchText) }}">
                                    <td>
                                        <div class="payout-employee-cell">
                                            <strong>{{ $record->employee->full_name }}</strong>
                                            <span>{{ $record->employee->employee_id }} | {{ $record->employee->designation ?? 'Not specified' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="payout-bank-cell">
                                            <strong>{{ $savedBankLabel }}</strong>
                                            <span>{{ $savedAccountLabel }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $record->days_absent }}</td>
                                    <td>{{ $record->short_hours_days }}</td>
                                    <td>PKR {{ number_format($record->security_deduction, 2) }}</td>
                                    <td>PKR {{ number_format($record->gross_salary, 2) }}</td>
                                    <td>PKR {{ number_format($record->income_tax, 2) }}</td>
                                    <td class="net-highlight">PKR {{ number_format($record->net_salary, 2) }}</td>
                                    <td class="table-action-cell">
                                        <a href="{{ route('payroll.payslip.download', [$selectedRun, $record->employee]) }}" class="btn btn-outline small" style="text-decoration: none;">
                                            <i data-lucide="file-down"></i> Payslip
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="empty-state-panel" data-saved-payout-empty-search style="display: none; margin-top: 12px;">
                    No employees match the current search.
                </div>

                <div class="pagination-wrapper" style="margin-top: 18px;">
                    {{ $selectedRunRecords->links() }}
                </div>
            </div>
        @else
            <div class="card">
                <div class="empty-state-panel">
                    No payout exists for {{ $selectedMonthLabel }} yet. Use <strong>Create Payouts</strong> to prepare that month and generate the batch outputs.
                </div>
            </div>
        @endif
    </section>
</div>

@if($canGeneratePayroll)
    <div id="createPayoutModal" class="modal-overlay" style="display: none;">
        <div class="modal-container payout-modal">
            <div class="modal-header">
                <div>
                    <h2>Create Payouts</h2>
                    <p class="modal-desc">Select a month, review all active payroll rows in one table, then save and create the payout pack.</p>
                </div>
                <button type="button" onclick="closePayoutModal()" class="close-btn"><i data-lucide="x"></i></button>
            </div>

            <div class="modal-body">
                <form method="POST" action="{{ route('payroll.generate') }}" id="createPayoutForm">
                    @csrf
                    <input type="hidden" name="download_pack" value="1">

                    <div class="payout-modal-controls">
                        <div class="form-group" style="margin: 0;">
                            <label>Payout Month</label>
                            <input type="month" name="month" id="payoutMonthInput" value="{{ $payoutMonth }}">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Payment Date</label>
                            <input type="date" name="payment_date" value="{{ old('payment_date', \Illuminate\Support\Carbon::createFromFormat('Y-m', $payoutMonth)->copy()->addMonth()->startOfMonth()->toDateString()) }}">
                        </div>
                        <div class="form-group payout-notes-field" style="margin: 0;">
                            <label>Notes</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional payout notes">
                        </div>
                    </div>

                    <div class="payout-preview-header">
                        <div>
                            <h3 id="payoutPreviewTitle">{{ $payoutMonthLabel }} Preview</h3>
                            <p id="payoutPreviewCaption">{{ $payoutPreviewRows->count() }} employees ready for payout processing.</p>
                        </div>
                    </div>

                    <div id="payoutPreviewContainer">
                        @include('payroll.partials.payout-preview-table', [
                            'rows' => $payoutPreviewRows,
                            'canEditPayroll' => $canEditPayroll,
                        ])
                    </div>

                    <div class="modal-footer payout-modal-footer">
                        <button type="button" onclick="closePayoutModal()" class="btn btn-outline">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="payoutSubmitButton">
                            <i data-lucide="wallet-cards"></i> Save & Create Payout Pack
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<style>
    .payroll-page-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .payroll-overview-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card.compact {
        min-height: auto;
    }

    .payroll-workspace {
        display: grid;
        grid-template-columns: minmax(260px, 300px) minmax(0, 1fr);
        gap: 24px;
        align-items: start;
        min-width: 0;
    }

    .payroll-history-panel {
        position: sticky;
        top: 24px;
        min-width: 0;
    }

    .payroll-history-list {
        display: grid;
        gap: 12px;
        max-height: 72vh;
        overflow-y: auto;
        padding-right: 4px;
    }

    .payroll-history-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 15px;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        text-decoration: none;
        color: inherit;
        background: #fff;
    }

    .payroll-history-item.active {
        border-color: #fed7aa;
        background: #fff7ed;
    }

    .payroll-history-item strong {
        display: block;
        color: #111827;
        margin-bottom: 4px;
    }

    .payroll-history-item p {
        margin: 0;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.5;
    }

    .history-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .history-status.draft {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .history-status.finalized {
        background: #ecfdf5;
        color: #047857;
    }

    .payroll-detail-panel {
        display: grid;
        gap: 20px;
        min-width: 0;
    }

    .payroll-detail-panel > .card,
    .payroll-workspace > * {
        min-width: 0;
    }

    .payroll-detail-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .selected-run-status-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
    }

    .detail-status-label {
        font-size: 12px;
        color: #6b7280;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .detail-metric {
        padding: 14px 16px;
        border: 1px solid #edf2f7;
        border-radius: 14px;
        background: #f9fafb;
    }

    .detail-metric span {
        display: block;
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 6px;
    }

    .detail-metric strong {
        color: #111827;
        font-size: 15px;
    }

    .payroll-action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        min-width: 0;
    }

    .danger-outline {
        color: #b42318;
        border-color: #fda29b;
        background: #fff5f4;
    }

    .danger-outline:hover {
        background: #ffe4e1;
        border-color: #f97066;
    }

    .button-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 22px;
        margin-left: 8px;
        padding: 0 6px;
        border-radius: 999px;
        background: #f3f4f6;
        font-size: 11px;
        font-weight: 700;
    }

    .payout-records-table-wrap,
    .payout-preview-table-wrap {
        overflow: auto;
        border: 1px solid #edf2f7;
        border-radius: 16px;
        max-width: 100%;
        width: 100%;
        max-height: 62vh;
    }

    .payout-records-table,
    .payout-preview-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1180px;
    }

    .payout-records-table th,
    .payout-records-table td,
    .payout-preview-table th,
    .payout-preview-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #edf2f7;
        vertical-align: middle;
        text-align: left;
        font-size: 13px;
    }

    .payout-records-table th,
    .payout-preview-table th {
        background: #f9fafb;
        color: #4b5563;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .payout-records-table tbody tr:last-child td,
    .payout-preview-table tbody tr:last-child td {
        border-bottom: none;
    }

    .payout-preview-table input {
        width: 100%;
        min-width: 96px;
        padding: 8px 10px;
        border: 1px solid #dbe2ea;
        border-radius: 8px;
        font-size: 13px;
    }

    .payout-preview-tools {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .payout-search-box {
        flex: 1 1 320px;
        min-width: 0;
    }

    .payout-search-box input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        font-size: 13px;
        background: #fff;
    }

    .payout-employee-cell,
    .payout-bank-cell {
        display: grid;
        gap: 4px;
    }

    .payout-employee-cell strong,
    .payout-bank-cell strong {
        color: #111827;
    }

    .payout-employee-cell span,
    .payout-bank-cell span {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.4;
    }

    .net-highlight,
    .payout-net-cell {
        font-weight: 700;
        color: #111827;
        white-space: nowrap;
    }

    .table-action-cell {
        white-space: nowrap;
        text-align: right;
    }

    .btn.small {
        padding: 8px 12px;
        font-size: 12px;
    }

    .empty-state-panel.small {
        padding: 20px;
        border-radius: 12px;
    }

    .payout-modal {
        max-width: 1360px;
        width: calc(100vw - 80px);
    }

    .payout-modal .modal-body {
        padding: 20px 24px 24px;
    }

    .payout-modal-controls {
        display: grid;
        grid-template-columns: minmax(160px, 200px) minmax(180px, 220px) minmax(0, 1fr);
        gap: 16px;
        margin-bottom: 18px;
        align-items: end;
    }

    .payout-preview-header {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .payout-preview-header h3 {
        margin: 0 0 4px;
        color: #111827;
    }

    .payout-preview-header p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }

    .payout-modal-footer {
        padding: 18px 0 0;
        margin-top: 18px;
        border-top: 1px solid #edf2f7;
    }

    @media (max-width: 1200px) {
        .payroll-overview-stats,
        .payroll-detail-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .payroll-workspace {
            grid-template-columns: 1fr;
        }

        .payroll-history-panel {
            position: static;
        }
    }

    @media (max-width: 768px) {
        .payroll-page-header {
            flex-direction: column;
        }

        .payroll-overview-stats,
        .payroll-detail-metrics,
        .payout-modal-controls {
            grid-template-columns: 1fr;
        }

        .payout-modal {
            width: calc(100vw - 20px);
        }

        .payroll-action-row {
            flex-direction: column;
            align-items: stretch;
        }

        .payout-preview-tools {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

@if($canGeneratePayroll)
    <script>
        (() => {
            const modal = document.getElementById('createPayoutModal');
            const monthInput = document.getElementById('payoutMonthInput');
            const previewContainer = document.getElementById('payoutPreviewContainer');
            const previewTitle = document.getElementById('payoutPreviewTitle');
            const previewCaption = document.getElementById('payoutPreviewCaption');
            const submitButton = document.getElementById('payoutSubmitButton');
            const paymentDateInput = document.querySelector('#createPayoutForm [name="payment_date"]');
            const notesInput = document.querySelector('#createPayoutForm [name="notes"]');
            const previewUrl = @json(route('payroll.payout-preview'));
            let previewController = null;

            function bindPayoutPreviewInteractions() {
                const searchInput = previewContainer.querySelector('[data-payout-search]');
                const rows = Array.from(previewContainer.querySelectorAll('[data-payout-row]'));
                const emptyState = previewContainer.querySelector('[data-payout-empty-search]');
                const countNode = previewContainer.querySelector('[data-payout-search-count]');

                if (!searchInput || rows.length === 0) {
                    return;
                }

                const applyFilter = () => {
                    const term = searchInput.value.trim().toLowerCase();
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const haystack = row.dataset.searchText || '';
                        const matches = term === '' || haystack.includes(term);
                        row.style.display = matches ? '' : 'none';

                        if (matches) {
                            visibleCount++;
                        }
                    });

                    if (countNode) {
                        countNode.textContent = `${visibleCount} employee${visibleCount === 1 ? '' : 's'}`;
                    }

                    if (emptyState) {
                        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
                    }
                };

                searchInput.addEventListener('input', applyFilter);
                applyFilter();
            }

            function bindSavedPayoutSearch() {
                const searchInput = document.querySelector('[data-saved-payout-search]');
                const rows = Array.from(document.querySelectorAll('[data-saved-payout-row]'));
                const emptyState = document.querySelector('[data-saved-payout-empty-search]');
                const countNode = document.querySelector('[data-saved-payout-search-count]');

                if (!searchInput || rows.length === 0) {
                    return;
                }

                const applyFilter = () => {
                    const term = searchInput.value.trim().toLowerCase();
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const haystack = row.dataset.searchText || '';
                        const matches = term === '' || haystack.includes(term);
                        row.style.display = matches ? '' : 'none';

                        if (matches) {
                            visibleCount++;
                        }
                    });

                    if (countNode) {
                        countNode.textContent = `${visibleCount} employee${visibleCount === 1 ? '' : 's'}`;
                    }

                    if (emptyState) {
                        emptyState.style.display = visibleCount === 0 ? '' : 'none';
                    }
                };

                searchInput.addEventListener('input', applyFilter);
                applyFilter();
            }

            function defaultPaymentDate(month) {
                if (!month) {
                    return '';
                }

                const [year, monthNumber] = month.split('-').map(Number);
                const date = new Date(year, monthNumber, 1);
                return date.toISOString().slice(0, 10);
            }

            window.openPayoutModal = function (month = @json($payoutMonth), paymentDate = '', notes = '', isEditing = false) {
                monthInput.value = month || @json($payoutMonth);
                paymentDateInput.value = paymentDate || defaultPaymentDate(monthInput.value);
                notesInput.value = notes || '';
                submitButton.innerHTML = isEditing
                    ? '<i data-lucide="square-pen"></i> Save Changes & Regenerate Pack'
                    : '<i data-lucide="wallet-cards"></i> Save & Create Payout Pack';

                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                loadPreview(monthInput.value);
                if (window.lucide) window.lucide.createIcons();
            };

            window.closePayoutModal = function () {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            };

            async function loadPreview(month) {
                if (!month) {
                    return;
                }

                if (previewController) {
                    previewController.abort();
                }

                previewController = new AbortController();
                previewContainer.innerHTML = '<div class="empty-state-panel">Loading payout preview...</div>';

                try {
                    const response = await fetch(`${previewUrl}?month=${encodeURIComponent(month)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        signal: previewController.signal,
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to load payout preview.');
                    }

                    previewContainer.innerHTML = payload.html;
                    previewTitle.textContent = `${payload.month_label} Preview`;
                    previewCaption.textContent = `${payload.row_count} employees ready for payout processing.`;
                    bindPayoutPreviewInteractions();
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    previewContainer.innerHTML = '<div class="empty-state-panel">Unable to load payout preview for the selected month.</div>';
                }
            }

            monthInput?.addEventListener('change', (event) => {
                loadPreview(event.target.value);
            });

            window.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closePayoutModal();
                }
            });

            bindPayoutPreviewInteractions();
            bindSavedPayoutSearch();
        })();
    </script>
@endif

@if(session('auto_download_payslip_zip'))
    <script>
        window.addEventListener('load', () => {
            window.location.href = @json(session('auto_download_payslip_zip'));
        });
    </script>
@endif
@endsection
