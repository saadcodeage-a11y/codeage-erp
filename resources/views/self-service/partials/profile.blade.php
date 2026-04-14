<section class="table-card {{ $activeTab === 'profile' ? '' : 'hidden-tab' }}">
    <div class="section-head">
        <h2>Profile</h2>
        <p>Read-only employee profile and employment details.</p>
    </div>
    <div class="info-grid">
        <div class="info-card"><span class="label">Full Name</span><strong>{{ $employee->full_name }}</strong></div>
        <div class="info-card"><span class="label">Employee ID</span><strong>{{ $employee->employee_id ?: 'Pending ID' }}</strong></div>
        <div class="info-card"><span class="label">Email</span><strong>{{ $employee->email ?: 'N/A' }}</strong></div>
        <div class="info-card"><span class="label">Phone</span><strong>{{ $employee->phone ?: 'N/A' }}</strong></div>
        <div class="info-card"><span class="label">Department</span><strong>{{ $employee->department?->name ?? 'N/A' }}</strong></div>
        <div class="info-card"><span class="label">Designation</span><strong>{{ $employee->designation ?: 'N/A' }}</strong></div>
        <div class="info-card"><span class="label">Hiring Date</span><strong>{{ $employee->hiring_date?->format('d M Y') ?? 'N/A' }}</strong></div>
        <div class="info-card"><span class="label">Payroll Status</span><strong>{{ $employee->payroll_status ?: 'N/A' }}</strong></div>
        <div class="info-card"><span class="label">Payment Mode</span><strong>{{ $employee->payment_mode ?: 'N/A' }}</strong></div>
        <div class="info-card"><span class="label">Team Manager</span><strong>{{ $employee->teamManager?->name ?? 'Not assigned' }}</strong></div>
        <div class="info-card"><span class="label">Bank</span><strong>{{ $employee->bank_name ?: 'N/A' }}</strong></div>
        <div class="info-card"><span class="label">IBAN / Account</span><strong>{{ $employee->iban ?: ($employee->bank_account_number ?: 'N/A') }}</strong></div>
    </div>
</section>
