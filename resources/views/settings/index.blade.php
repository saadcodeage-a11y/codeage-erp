@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<!-- Quill Rich Text Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 16px;">
        <div style="background: #fff7ed; padding: 12px; border-radius: 12px; border: 1px solid #ffedd5; color: #f97316;">
            <i data-lucide="settings" style="width: 24px; height: 24px;"></i>
        </div>
        <div>
            <h1 style="font-size: 24px; font-weight: 600; color: #111827; margin: 0;">System Settings</h1>
            <p style="color: #6b7280; font-size: 14px; margin: 4px 0 0 0;">Configure system-wide settings and integrations</p>
        </div>
    </div>
</div>

<div class="tabs-container">
    <button class="tab-item active" onclick="switchTab('email-service', this)">
        Email Service
    </button>
    <button class="tab-item" onclick="switchTab('smtp-config', this)">
        SMTP Configuration
    </button>
    <button class="tab-item" onclick="switchTab('bank-list', this)">
        Bank List
    </button>
    <button class="tab-item" onclick="switchTab('policies', this)">
        Company Policies
    </button>
    <button class="tab-item" onclick="switchTab('tax-formulas', this)">
        Tax Formulas
    </button>
    <button class="tab-item" onclick="switchTab('system-values', this)">
        System Values
    </button>
    <button class="tab-item" onclick="switchTab('other', this)">
        Other Settings
    </button>
</div>

<!-- Email Service Tab -->
<div id="email-service" class="settings-tab-content">
    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 18px; font-weight: 600; margin: 0;">Email Service Status</h2>
            <button class="btn btn-outline" style="font-size: 13px; height: 36px;">
                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> Check Status
            </button>
        </div>

        <div class="status-list" style="display: flex; flex-direction: column; gap: 12px;">
            <div class="status-card error">
                <div class="status-icon">
                    <i data-lucide="x-circle"></i>
                </div>
                <div class="status-info">
                    <h3>Resend API</h3>
                    <p>Status unknown - click "Check Status"</p>
                </div>
            </div>

            <div class="status-card warning">
                <div class="status-icon">
                    <i data-lucide="alert-triangle"></i>
                </div>
                <div class="status-info">
                    <h3>SMTP Configuration</h3>
                    <p>{{ $defaultSmtp ? 'Configured: ' . $defaultSmtp->name : 'No SMTP configured (will use Resend)' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="info-banner" style="margin-bottom: 24px;">
        <div style="display: flex; gap: 12px;">
            <i data-lucide="info" style="color: #3b82f6; flex-shrink: 0; width: 20px; height: 20px;"></i>
            <div>
                <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight: 600; color: #1e40af;">How to Configure Email Service</h4>
                <p style="margin: 0; font-size: 13px; color: #1e40af; line-height: 1.5;">
                    Your system uses a smart fallback system:<br>
                    1. First tries SMTP (if configured in "SMTP Configuration" tab)<br>
                    2. Falls back to Resend API (requires RESEND_API_KEY environment variable)<br>
                    3. If neither works, shows codes in console for development
                </p>
                <p style="margin-top: 8px; font-size: 13px; font-weight: 600; color: #1e40af;">
                    To enable Resend: Check the browser console for messages about RESEND_API_KEY. If it's not configured, the system will prompt you to add it.
                </p>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 18px; font-weight: 600; margin: 0;">Send Test Email</h2>
            <button onclick="sendTestEmail()" class="btn btn-primary" style="background: #111827; border: none; font-size: 13px; height: 36px;">
                <i data-lucide="send" style="width: 14px; height: 14px;"></i> Send Test Email
            </button>
        </div>
        <div class="form-group">
            <label style="font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; display: block;">Recipient Email</label>
            <input type="email" id="test_recipient" class="form-control" placeholder="test@example.com" style="width: 100%; max-width: 400px; background: #f9fafb;">
        </div>
    </div>

    <div class="info-banner success" style="margin-bottom: 24px; border-color: #bbf7d0; background: #f0fdf4;">
        <div style="display: flex; gap: 12px;">
            <i data-lucide="check-circle" style="color: #22c55e; flex-shrink: 0; width: 20px; height: 20px;"></i>
            <div>
                <h4 style="margin: 0 0 2px 0; font-size: 14px; font-weight: 600; color: #166534;">Default Email Service Active</h4>
                <p style="margin: 0; font-size: 13px; color: #166534;">Your system has automatic email delivery enabled using Resend API. Emails will be sent automatically even without SMTP configuration.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <i data-lucide="mail-plus" style="color: #f97316; width: 20px; height: 20px;"></i>
            <h2 style="font-size: 18px; font-weight: 600; margin: 0;">Email Delivery Flow</h2>
        </div>
        <div class="flow-steps">
            <div class="flow-step">
                <div class="step-num">1</div>
                <div class="step-content">
                    <h5>Try SMTP First</h5>
                    <p>If you've configured SMTP, it will be used first</p>
                </div>
            </div>
            <div class="flow-step">
                <div class="step-num">2</div>
                <div class="step-content">
                    <h5>Fallback to Resend</h5>
                    <p>If SMTP fails or isn't configured, Resend API is used</p>
                </div>
            </div>
            <div class="flow-step">
                <div class="step-num">3</div>
                <div class="step-content">
                    <h5>Console Logging</h5>
                    <p>If no email service is available, codes are logged to console (dev mode)</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SMTP Configuration Tab -->
<div id="smtp-config" class="settings-tab-content" style="display: none;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 18px; font-weight: 600; margin: 0;">SMTP Configurations</h2>
            <p style="color: #6b7280; font-size: 14px; margin: 4px 0 0 0;">Manage multiple email accounts for different purposes (HR, Accounts, System, etc.)</p>
        </div>
        <button class="btn btn-primary" style="background: #FF4A00;" onclick="openSmtpModal()">
            <i data-lucide="plus"></i> Add SMTP Account
        </button>
    </div>

    @foreach($smtpConfigs as $config)
    <div class="card smtp-account-card" style="margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <h3 style="font-size: 16px; font-weight: 600; margin: 0;">{{ $config->name }}</h3>
                    @if($config->is_default)
                    <span class="badge" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">Default</span>
                    @endif
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div>
                        <p style="font-size: 12px; color: #6b7280; margin: 0 0 4px 0;">From:</p>
                        <p style="font-size: 14px; color: #374151; font-weight: 500;">{{ $config->from_name }} &lt;{{ $config->from_email }}&gt;</p>
                        <p style="font-size: 12px; color: #6b7280; margin: 12px 0 4px 0;">Username:</p>
                        <p style="font-size: 14px; color: #374151; font-weight: 500;">{{ $config->username }}</p>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: #6b7280; margin: 0 0 4px 0;">Server:</p>
                        <p style="font-size: 14px; color: #374151; font-weight: 500;">{{ $config->host }}:{{ $config->port }} ({{ strtoupper($config->encryption) }})</p>
                        @if(!$config->is_default)
                        <button onclick="setDefaultSmtp({{ $config->id }})" class="btn btn-outline" style="margin-top: 12px; height: 32px; font-size: 12px; padding: 0 12px;">
                            <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i> Set as Default
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="action-buttons-group">
                <button class="icon-btn" onclick="testSmtp({{ $config->id }})" title="Test Connection"><i data-lucide="send"></i></button>
                <button class="icon-btn" onclick='editSmtp(@json($config))' title="Edit"><i data-lucide="edit-3"></i></button>
                <button class="icon-btn delete" onclick="deleteSmtp({{ $config->id }})" title="Delete"><i data-lucide="trash-2"></i></button>
            </div>
        </div>
    </div>
    @endforeach

    @if($smtpConfigs->isEmpty())
    <div class="empty-state">
        <i data-lucide="mail"></i>
        <p>No SMTP configurations found. Add one to start sending emails.</p>
    </div>
    @endif

    <div class="info-banner" style="margin-top: 32px; border-style: dashed;">
        <div style="display: flex; gap: 12px;">
            <i data-lucide="lightbulb" style="color: #f59e0b; flex-shrink: 0; width: 20px; height: 20px;"></i>
            <div>
                <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #1e40af;">Tip:</h4>
                <p style="margin: 0; font-size: 13px; color: #1e40af; line-height: 1.6;">
                    Configure separate SMTP accounts for different departments:<br>
                    <strong style="color: #1e3a8a;">HR Email:</strong> For recruitment, onboarding, employee communications<br>
                    <strong style="color: #1e3a8a;">Accounts Email:</strong> For invoices, payment reminders, financial reports<br>
                    <strong style="color: #1e3a8a;">System Email:</strong> For 2FA, password resets, system notifications
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Bank List Tab -->
<div id="bank-list" class="settings-tab-content" style="display: none;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 18px; font-weight: 600; margin: 0;">Bank List</h2>
            <p style="color: #6b7280; font-size: 14px; margin: 4px 0 0 0;">Manage bank names and codes for employee banking details</p>
        </div>
        <button class="btn btn-primary" style="background: #FF4A00;" onclick="openBankModal()">
            <i data-lucide="plus"></i> Add Bank
        </button>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bank Name</th>
                    <th>Bank Code</th>
                    <th>Created At</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($banks as $bank)
                <tr>
                    <td>
                        <div style="font-weight: 500; color: #111827;">{{ $bank->name }}</div>
                    </td>
                    <td>
                        <span class="badge" style="background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; font-family: monospace;">{{ $bank->code }}</span>
                    </td>
                    <td>{{ $bank->created_at->format('d/m/Y') }}</td>
                    <td style="text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                            <button onclick="editBank({{ $bank->id }}, '{{ $bank->name }}', '{{ $bank->code }}')" class="icon-btn" style="color: #6b7280;"><i data-lucide="edit-3"></i></button>
                            <button onclick="deleteBank({{ $bank->id }})" class="icon-btn delete" style="color: #ef4444;"><i data-lucide="trash-2"></i></button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Company Policies Tab -->
<div id="policies" class="settings-tab-content" style="display: none;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 18px; font-weight: 600; margin: 0;">Company Policy Sections</h2>
            <p style="color: #6b7280; font-size: 14px; margin: 4px 0 0 0;">Manage policy sections that appear in the employee onboarding form</p>
        </div>
        <button class="btn btn-primary" style="background: #FF4A00;" onclick="openPolicyModal()">
            <i data-lucide="plus"></i> Add Section
        </button>
    </div>

    <div class="policy-sections-list">
        @foreach($policies as $index => $policy)
        <div class="card policy-card {{ !$policy->is_visible ? 'hidden-policy' : '' }}" style="margin-bottom: 16px; padding: 20px;">
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div class="policy-drag-handle">
                    <i data-lucide="grip-vertical" style="color: #d1d5db;"></i>
                    <span style="font-size: 11px; color: #9ca3af; font-weight: 600;">{{ $index + 1 }}</span>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <h3 style="font-size: 16px; font-weight: 600; margin: 0; color: #111827;">{{ $policy->title }}</h3>
                                @if($policy->is_visible)
                                <span class="badge" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 4px;">
                                    <i data-lucide="eye" style="width: 10px; height: 10px;"></i> Visible
                                </span>
                                @else
                                <span class="badge" style="background: #f9fafb; color: #6b7280; border: 1px solid #e5e7eb; display: inline-flex; align-items: center; gap: 4px;">
                                    <i data-lucide="eye-off" style="width: 10px; height: 10px;"></i> Hidden
                                </span>
                                @endif
                            </div>
                            <div class="policy-content-preview" style="color: #6b7280; font-size: 14px; line-height: 1.6;">
                                {!! Str::limit(strip_tags($policy->content), 200) !!}
                            </div>
                        </div>
                        <div class="action-buttons-group">
                            <div style="display: flex; flex-direction: column; gap: 4px; margin-right: 8px;">
                                <button onclick="reorderPolicy({{ $policy->id }}, 'up')" class="reorder-btn" title="Move Up"><i data-lucide="chevron-up"></i></button>
                                <button onclick="reorderPolicy({{ $policy->id }}, 'down')" class="reorder-btn" title="Move Down"><i data-lucide="chevron-down"></i></button>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label class="switch-toggle">
                                    <input type="checkbox" {{ $policy->is_visible ? 'checked' : '' }} onchange="togglePolicyVisibility({{ $policy->id }})">
                                    <span class="slider"></span>
                                </label>
                                <button class="icon-btn" onclick='editPolicy(@json($policy))' title="Edit"><i data-lucide="edit-3"></i></button>
                                <button class="icon-btn delete" onclick="deletePolicy({{ $policy->id }})" title="Delete"><i data-lucide="trash-2"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        @if($policies->isEmpty())
        <div class="empty-state">
            <i data-lucide="file-text"></i>
            <p>No policy sections found. Add one to show in the onboarding form.</p>
        </div>
        @endif
    </div>
</div>

<!-- Tax Formulas Tab -->
<div id="tax-formulas" class="settings-tab-content" style="display: none;">
    <div class="card" style="margin-bottom: 24px;">
        <div class="tax-formula-header">
            <div>
                <h2 style="font-size: 18px; font-weight: 600; margin: 0;">Tax Calculation Formulas</h2>
                <p style="color: #6b7280; font-size: 14px; margin: 4px 0 0 0;">Define the taxable-income expression and slab formulas used by payroll generation.</p>
            </div>
            <button onclick="saveTaxFormulas()" class="btn btn-primary" style="background: #111827;">
                <i data-lucide="save"></i> Save Tax Rules
            </button>
        </div>

        <div class="tax-formula-summary">
            <div class="tax-formula-summary-card">
                <span>Active Slabs</span>
                <strong id="taxSlabCount">{{ count($taxFormulaConfig['slabs']) }}</strong>
            </div>
            <div class="tax-formula-summary-card wide">
                <span>Current Taxable Income Formula</span>
                <strong id="taxFormulaPreview">{{ $taxFormulaConfig['taxable_income_formula'] }}</strong>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
            <div style="background: #eff6ff; padding: 10px; border-radius: 10px; color: #2563eb;">
                <i data-lucide="function-square" style="width: 20px; height: 20px;"></i>
            </div>
            <div>
                <h3 style="font-size: 16px; font-weight: 600; margin: 0;">Taxable Income Formula</h3>
                <p style="color: #6b7280; font-size: 13px; margin: 2px 0 0 0;">This formula is evaluated first. The resulting value is then matched against the slabs below.</p>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 18px;">
            <label>Formula</label>
            <input type="text" id="taxable_income_formula" class="form-control formula-builder-target" value="{{ $taxFormulaConfig['taxable_income_formula'] }}" placeholder="e.g. gross_salary or basic_salary + last_increment" style="background: #f9fafb; font-family: monospace;">
            <span class="field-hint">Use payroll variables, numbers, and arithmetic operators. Parentheses and `%`, `^`, `*`, `/`, `+`, `-` are supported.</span>
        </div>

        <div class="tax-builder-toolbar">
            @foreach(['+', '-', '*', '/', '%', '^', '(', ')'] as $operator)
                <button type="button" class="formula-token-btn operator" onclick="insertFormulaToken('{{ $operator }}')">{{ $operator }}</button>
            @endforeach
        </div>

        <div class="tax-variable-panel">
            <div class="tax-variable-panel-header">
                <h4>Payroll Variables</h4>
                <input type="text" id="taxVariableSearch" class="form-control" placeholder="Search variables..." style="max-width: 240px; background: #fff;">
            </div>
            <div class="info-banner" style="margin-bottom: 14px; background: #fff7ed; border-color: #fed7aa;">
                <div style="display: flex; gap: 12px;">
                    <i data-lucide="lightbulb" style="color: #c2410c; flex-shrink: 0; width: 18px; height: 18px;"></i>
                    <div>
                        <h4 style="margin: 0 0 4px 0; font-size: 13px; font-weight: 600; color: #c2410c;">Examples</h4>
                        <p style="margin: 0; font-size: 13px; color: #9a3412; line-height: 1.6;">
                            Example taxable formula: <strong>(basic_salary + last_increment) - security_deduction</strong><br>
                            Example slab formula: <strong>(taxable_income - slab_min) * 0.01</strong>
                        </p>
                    </div>
                </div>
            </div>
            <div class="tax-variable-grid">
                @foreach($taxFormulaVariables as $variable => $description)
                    <button type="button" class="formula-token-btn variable" data-variable-search="{{ strtolower($variable . ' ' . $description) }}" onclick="insertFormulaToken('{{ $variable }}')" title="{{ $description }}">
                        <strong>{{ $variable }}</strong>
                        <span>{{ $description }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="tax-formula-header" style="margin-bottom: 20px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 600; margin: 0;">Tax Slabs</h3>
                <p style="color: #6b7280; font-size: 13px; margin: 4px 0 0 0;">Each slab contains a min/max range and the formula used when taxable income falls within that range.</p>
            </div>
            <button type="button" class="btn btn-outline" onclick="addTaxSlabRow()">
                <i data-lucide="plus"></i> Add Slab
            </button>
        </div>

        <div class="info-banner" style="margin-bottom: 20px; background: #f8fafc; border-color: #dbeafe;">
            <div style="display: flex; gap: 12px;">
                <i data-lucide="info" style="color: #2563eb; flex-shrink: 0; width: 18px; height: 18px;"></i>
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 13px; font-weight: 600; color: #1d4ed8;">Slab Formula Variables</h4>
                    <p style="margin: 0; font-size: 13px; color: #1d4ed8; line-height: 1.6;">
                        Slab formulas can use all payroll variables plus <strong>taxable_income</strong>, <strong>slab_min</strong>, and <strong>slab_max</strong>.
                    </p>
                </div>
            </div>
        </div>

        <div id="taxSlabsList" class="tax-slab-list">
            @foreach($taxFormulaConfig['slabs'] as $slab)
                <div class="tax-slab-row">
                    <div class="tax-slab-grid">
                        <div class="form-group" style="margin: 0;">
                            <label>Label</label>
                            <input type="text" class="form-control tax-slab-label" value="{{ $slab['label'] }}" placeholder="e.g. Above 100,000" style="background: #f9fafb;">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Min</label>
                            <input type="number" step="0.01" class="form-control tax-slab-min" value="{{ $slab['min'] }}" style="background: #f9fafb;">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Max</label>
                            <input type="number" step="0.01" class="form-control tax-slab-max" value="{{ $slab['max'] }}" placeholder="Leave blank for open ended" style="background: #f9fafb;">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Taxable Income Formula</label>
                            <input type="text" class="form-control tax-slab-taxable-formula formula-builder-target" value="{{ $slab['taxable_income_formula'] }}" placeholder="Leave blank to use default taxable formula" style="background: #f9fafb; font-family: monospace;">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label>Tax Formula</label>
                            <input type="text" class="form-control tax-slab-formula formula-builder-target" value="{{ $slab['formula'] }}" placeholder="e.g. (taxable_income - 50000) * 0.01" style="background: #f9fafb; font-family: monospace;">
                        </div>
                    </div>
                    <div class="tax-slab-actions">
                        <button type="button" class="btn btn-outline" onclick="removeTaxSlabRow(this)">
                            <i data-lucide="trash-2"></i> Remove
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card" style="margin-top: 24px;">
        <div class="tax-formula-header" style="margin-bottom: 18px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 600; margin: 0;">Employee Formula Example</h3>
                <p style="color: #6b7280; font-size: 13px; margin: 4px 0 0 0;">Search an employee and inspect the current payroll variables available to formulas.</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <input type="text" id="taxExampleEmployeeSearch" class="form-control" list="taxExampleEmployees" placeholder="Search employee by name or ID" style="min-width: 280px; background: #f9fafb;">
                <button type="button" class="btn btn-outline" onclick="loadTaxFormulaExample()">
                    <i data-lucide="search"></i> Load Example
                </button>
            </div>
        </div>
        <datalist id="taxExampleEmployees">
            @foreach($formulaExampleEmployees as $employee)
                <option value="{{ $employee->employee_id }} | {{ $employee->full_name }}" data-id="{{ $employee->id }}"></option>
            @endforeach
        </datalist>

        <div id="taxExampleState" class="note-panel">Select an employee to preview variable values from the current payroll month.</div>
        <div id="taxExampleGrid" class="tax-example-grid" style="display: none;"></div>
    </div>
</div>

<!-- Other Settings Tab -->
<div id="other" class="settings-tab-content" style="display: none;">
    <!-- Employee ID Configuration -->
    <div class="card" style="margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="background: #fff7ed; padding: 10px; border-radius: 10px; color: #f97316;">
                <i data-lucide="hash" style="width: 20px; height: 20px;"></i>
            </div>
            <div>
                <h2 style="font-size: 18px; font-weight: 600; margin: 0;">Employee ID Configuration</h2>
                <p style="color: #6b7280; font-size: 14px; margin: 2px 0 0 0;">Configure how employee IDs are generated in your system</p>
            </div>
        </div>

        <div style="max-width: 600px;">
            <div class="form-group">
                <label>Employee ID Prefix</label>
                <div style="display: flex; gap: 12px;">
                    <input type="text" id="id_prefix" class="form-control" value="{{ $employeeIdPrefix }}" placeholder="e.g., EMP" style="max-width: 200px; background: #f9fafb;">
                    <button onclick="updateGeneralSettings('id_prefix')" class="btn btn-primary" style="background: #111827;">Save Prefix</button>
                </div>
                <span class="hint">This prefix will be added before the employee number (e.g., <span id="employee_id_hint_one">{{ $employeeIdPrefix }}001</span>, <span id="employee_id_hint_two">{{ $employeeIdPrefix }}002</span>)</span>
            </div>

            <div style="margin-top: 24px; padding: 16px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 12px; color: #6b7280; margin: 0 0 4px 0;">Current Status</p>
                        <div style="display: flex; gap: 32px;">
                            <div>
                                <span style="font-size: 12px; color: #9ca3af;">Current Number</span>
                                <p id="employee_id_current_number" style="font-size: 18px; font-weight: 600; margin: 4px 0 0 0;">{{ $employeeIdCounter }}</p>
                            </div>
                            <div>
                                <span style="font-size: 12px; color: #9ca3af;">Next Employee ID</span>
                                <p id="employee_id_next" style="font-size: 18px; font-weight: 600; margin: 4px 0 0 0; color: #f97316;">{{ $nextEmployeeId }}</p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-outline" style="height: 36px; font-size: 13px;" onclick="resetEmployeeIdCounter()">
                        <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> Reset Counter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- HR Notification Emails -->
    <div class="card">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <div style="background: #eff6ff; padding: 10px; border-radius: 10px; color: #2563eb;">
                <i data-lucide="mail" style="width: 20px; height: 20px;"></i>
            </div>
            <div>
                <h2 style="font-size: 18px; font-weight: 600; margin: 0;">HR Notification Emails</h2>
                <p style="color: #6b7280; font-size: 14px; margin: 2px 0 0 0;">Email addresses that will receive notifications when employees complete onboarding</p>
            </div>
        </div>

        <div class="info-banner" style="margin-bottom: 20px; background: #fffcf0; border-color: #fef3c7; color: #92400e;">
            <i data-lucide="info" style="width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;"></i>
            <span style="font-size: 13px;">Enter email addresses separated by commas. Example: hr@codeage.pk, manager@codeage.pk</span>
        </div>

        <div class="form-group">
            <label>HR Email Addresses</label>
            <textarea id="hr_emails" class="form-control" rows="3" placeholder="hr@codeage.pk, admin@codeage.pk" style="background: #f9fafb;">{{ $hrEmails }}</textarea>
            <span class="hint">Separate multiple email addresses with commas</span>
        </div>

        <div style="margin-top: 24px; padding: 16px; background: #fffafb; border-radius: 12px; border: 1px solid #fee2e2;">
            <h4 style="font-size: 14px; font-weight: 600; margin: 0 0 12px 0;">When notifications are sent:</h4>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #6b7280; display: flex; flex-direction: column; gap: 6px;">
                <li>When an employee completes the onboarding form</li>
                <li>When employee status changes to "Pending Approval"</li>
                <li>Template used: "Pending Approval Notification (to HR)"</li>
            </ul>
        </div>

        <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
            <button onclick="updateGeneralSettings('hr_emails')" class="btn btn-primary" style="background: #FF4A00;">
                <i data-lucide="save"></i> Save HR Emails
            </button>
        </div>
    </div>
</div>

<!-- System Values Tab -->
<div id="system-values" class="settings-tab-content" style="display: none;">
    <div class="card" style="max-width: 800px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
            <div style="background: #eff6ff; padding: 10px; border-radius: 10px; color: #3b82f6;">
                <i data-lucide="component" style="width: 20px; height: 20px;"></i>
            </div>
            <div>
                <h2 style="font-size: 18px; font-weight: 600; margin: 0;">System Values</h2>
                <p style="color: #6b7280; font-size: 13px; margin: 2px 0 0 0;">Configure dynamic values for email templates</p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="form-group">
                <label style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">Office Location</label>
                <input type="text" id="office_location" class="form-control" value="{{ $officeLocation }}" placeholder="e.g. Office 101, Business Center, City" style="width: 100%; border-radius: 8px; padding: 12px; border: 1px solid #e5e7eb;">
                <small style="color: #6b7280; margin-top: 6px; display: block;">This will replace &#123;&#123;officeLocation&#125;&#125; in email templates.</small>
            </div>

            <div class="form-group">
                <label style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">HR Contact Person</label>
                <input type="text" id="hr_contact" class="form-control" value="{{ $hrContact }}" placeholder="e.g. John Doe (HR Manager)" style="width: 100%; border-radius: 8px; padding: 12px; border: 1px solid #e5e7eb;">
                <small style="color: #6b7280; margin-top: 6px; display: block;">This will replace &#123;&#123;hrContact&#125;&#125; in email templates.</small>
            </div>

            <div style="margin-top: 8px; padding-top: 24px; border-top: 1px solid #f3f4f6;">
                <button onclick="updateSystemValues()" class="btn btn-primary" style="background: #111827; border: none; height: 44px; padding: 0 24px;">
                    <i data-lucide="save" style="width: 18px; height: 18px;"></i> Save System Values
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bank Modal -->
<div id="bankModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2 id="bankModalTitle">Add New Bank</h2>
                <p class="modal-desc">Add a bank to the system for employee banking details</p>
            </div>
            <button onclick="closeBankModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="bankForm">
                @csrf
                <input type="hidden" id="bank_id" name="id">
                <div class="form-group">
                    <label>Bank Name *</label>
                    <input type="text" name="name" id="bank_name" placeholder="e.g., Habib Bank Limited" required style="background: #f9fafb;">
                </div>
                <div class="form-group">
                    <label>Bank Code / Abbreviations *</label>
                    <input type="text" name="code" id="bank_code" placeholder="e.g., HBL" required style="background: #f9fafb;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <button type="button" onclick="closeBankModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #FF4A00;">Save Bank</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SMTP Modal -->
<div id="smtpModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 800px;">
        <div class="modal-header" style="border-bottom: none; padding-bottom: 0;">
            <div>
                <h2 id="smtpModalTitle" style="font-size: 20px; font-weight: 600; color: #111827; margin: 0;">Add SMTP Configuration</h2>
                <p id="smtpModalDesc" class="modal-desc" style="font-size: 14px; color: #6b7280; margin: 4px 0 0 0;">Configure email server settings</p>
            </div>
            <button onclick="closeSmtpModal()" class="modal-cancel-btn">
                <i data-lucide="x"></i> Cancel
            </button>
        </div>
        <div class="modal-body" style="background: #fdfdfd; padding: 32px;">
            <form id="smtpForm">
                @csrf
                <input type="hidden" id="smtp_id" name="id">
                
                <!-- Account Details -->
                <div class="settings-section">
                    <h3 style="font-size: 15px; font-weight: 600; color: #111827; margin-bottom: 4px;">Account Details</h3>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">Name and purpose of this SMTP configuration</p>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="field-label">Configuration Name *</label>
                        <input type="text" name="name" id="smtp_name" placeholder="HR" required style="background: #f9fafb;">
                        <span class="field-hint">A descriptive name to identify this SMTP account</span>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="field-label">Purpose / Description</label>
                        <textarea name="description" id="smtp_description" rows="2" placeholder="e.g., Used for sending HR-related emails like recruitment, onboarding..." style="background: #f9fafb;"></textarea>
                    </div>

                    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 14px; font-weight: 500; color: #111827; display: block;">Set as Default</span>
                            <span style="font-size: 12px; color: #6b7280;">Use this for general system emails</span>
                        </div>
                        <label class="switch-toggle" style="margin: 0;">
                            <input type="checkbox" name="is_default" id="smtp_is_default" value="1">
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Email Server Settings -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <i data-lucide="mail"></i>
                        <h3>Email Server Settings</h3>
                    </div>
                    <p class="section-desc">Configure SMTP server connection details</p>
                    
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="field-label">SMTP Host *</label>
                            <input type="text" name="host" id="smtp_host" placeholder="smtp.gmail.com" required style="background: #f9fafb;">
                        </div>
                        <div class="form-group">
                            <label class="field-label">Port *</label>
                            <input type="number" name="port" id="smtp_port" placeholder="587" required style="background: #f9fafb;">
                        </div>
                    </div>

                    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                        <div>
                            <span style="font-size: 14px; font-weight: 500; color: #111827; display: block;">Use SSL/TLS (Port 465)</span>
                            <span style="font-size: 12px; color: #6b7280;">Enable for secure connection on port 465</span>
                        </div>
                        <label class="switch-toggle" style="margin: 0;">
                            <input type="checkbox" id="smtp_use_ssl" onchange="updateSmtpPort(this)">
                            <span class="slider"></span>
                        </label>
                        <input type="hidden" name="encryption" id="smtp_encryption" value="tls">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="field-label">Username / Email *</label>
                        <input type="text" name="username" id="smtp_username" placeholder="hr@codeagepk.com" required style="background: #f9fafb;">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="field-label">Password / App Password *</label>
                        <div class="input-with-icon">
                            <input type="password" name="password" id="smtp_password" required style="background: #f9fafb;">
                            <button type="button" class="input-icon-btn" onclick="togglePasswordVisibility('smtp_password')">
                                <i data-lucide="eye" id="toggle_password_icon" style="width: 18px; height: 18px;"></i>
                            </button>
                        </div>
                        <span class="field-hint">For Gmail, use App Password (not your regular password)</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="field-label">From Email *</label>
                            <input type="email" name="from_email" id="smtp_from_email" placeholder="hr@codeagepk.com" required style="background: #f9fafb;">
                        </div>
                        <div class="form-group">
                            <label class="field-label">From Name *</label>
                            <input type="text" name="from_name" id="smtp_from_name" placeholder="HR at CodeAge" required style="background: #f9fafb;">
                            <span class="field-hint">⚠️ Enter your company name (NOT an email address). This is what recipients see as the sender name.</span>
                        </div>
                    </div>
                </div>

                <!-- Test SMTP Connection -->
                <div class="settings-section" style="margin-bottom: 0;">
                    <div class="settings-section-header">
                        <i data-lucide="send"></i>
                        <h3>Test SMTP Connection</h3>
                    </div>
                    <p class="section-desc">Send a test email to verify your configuration</p>
                    
                    <div class="form-group">
                        <label class="field-label">Test Email Address</label>
                        <div class="test-connection-row">
                            <input type="email" id="modal_test_email" placeholder="test@example.com" style="background: #f9fafb;">
                            <button type="button" onclick="testSmtpInModal()" class="btn btn-outline" style="border: 1px solid #e5e7eb; height: 42px; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="send" style="width: 14px; height: 14px;"></i> Send Test
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="background: #fff; border-top: 1px solid #e5e7eb; padding: 20px 32px; display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" onclick="closeSmtpModal()" class="btn btn-outline" style="height: 42px; padding: 0 24px;">Discard</button>
            <button type="submit" form="smtpForm" class="btn btn-primary" style="background: #FF4A00; height: 42px; padding: 0 32px; font-weight: 600;">Save Configuration</button>
        </div>
    </div>
</div>

<!-- Add/Edit Policy Modal -->
<div id="policyModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 700px;">
        <div class="modal-header">
            <div>
                <h2 id="policyModalTitle">Add Policy Section</h2>
                <p class="modal-desc" style="margin-bottom: 0;">Add a new policy section to the employee onboarding form</p>
            </div>
            <button onclick="closePolicyModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="policyForm">
                @csrf
                <input type="hidden" id="policy_id" name="policy_id">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Section Title *</label>
                    <input type="text" name="title" id="policy_title" placeholder="e.g., Confidentiality Agreement" required style="background: #f9fafb;">
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label>Section Content *</label>
                    <div class="quill-wrapper" style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background: #fff;">
                        <div id="editor-container" style="height: 300px; border: none; font-family: 'Public Sans', sans-serif;"></div>
                    </div>
                    <input type="hidden" name="content" id="policy_content">
                </div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 24px;">
                    <label class="switch-toggle" style="margin:0;">
                        <input type="checkbox" name="is_visible" id="policy_is_visible" checked value="1">
                        <span class="slider"></span>
                    </label>
                    <span style="font-size: 14px; color: #4b5563;">Show this section in onboarding form</span>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button onclick="closePolicyModal()" class="btn btn-outline">Cancel</button>
            <button id="policySubmitBtn" form="policyForm" class="btn btn-primary" style="background: #FF4A00;">Add Section</button>
        </div>
    </div>
</div>

<style>
    .settings-tab-content { animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }


    .status-card { display: flex; align-items: center; gap: 16px; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; background: #f9fafb; }
    .status-card.error { border-color: #fee2e2; background: #fff5f5; }
    .status-card.error .status-icon { color: #ef4444; }
    .status-card.warning { border-color: #fef3c7; background: #fffcf0; }
    .status-card.warning .status-icon { color: #f59e0b; }
    .status-icon i { width: 32px; height: 32px; }
    .status-info h3 { font-size: 15px; font-weight: 600; margin: 0 0 4px 0; color: #111827; }
    .status-info p { font-size: 13px; color: #6b7280; margin: 0; }

    .info-banner { padding: 16px; border-radius: 12px; border: 1px solid #dbeafe; background: #eff6ff; }
    .flow-steps { display: flex; flex-direction: column; gap: 20px; position: relative; margin-left: 10px; }
    .flow-steps::before { content: ''; position: absolute; left: 15px; top: 20px; bottom: 20px; width: 2px; background: #f3f4f6; }
    .flow-step { display: flex; align-items: flex-start; gap: 20px; position: relative; z-index: 1; }
    .step-num { width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 2px solid #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; color: #111827; flex-shrink: 0; }
    .flow-step:nth-child(1) .step-num { border-color: #bbf7d0; background: #f0fdf4; color: #166534; }
    .flow-step:nth-child(2) .step-num { border-color: #fef3c7; background: #fffbeb; color: #92400e; }
    .step-content h5 { font-size: 14px; font-weight: 600; margin: 0 0 4px 0; }
    .step-content p { font-size: 13px; color: #6b7280; margin: 0; }

    .icon-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e5e7eb; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
    .icon-btn:hover { background: #f9fafb; border-color: #d1d5db; }
    .icon-btn.delete:hover { border-color: #fee2e2; background: #fff5f5; color: #ef4444 !important; }

    .empty-state { text-align: center; padding: 48px 24px; color: #6b7280; }
    .empty-state i { width: 48px; height: 48px; color: #d1d5db; margin-bottom: 16px; }
    .empty-state h3 { font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 8px 0; }

    /* Policy Specific Styles */
    .policy-card { transition: all 0.2s; position: relative; border: 1px solid #e5e7eb; }
    .policy-card:hover { border-color: #FF4A00; box-shadow: 0 4px 12px rgba(255, 74, 0, 0.05); }
    .hidden-policy { opacity: 0.6; background: #f9fafb; border-style: dashed; }
    .policy-drag-handle { display: flex; flex-direction: column; align-items: center; gap: 4px; padding-top: 4px; cursor: move; }
    .reorder-btn { border: none; background: none; padding: 2px; color: #9ca3af; cursor: pointer; transition: all 0.2s; }
    .reorder-btn:hover { color: #4b5563; background: #f3f4f6; border-radius: 4px; }
    .toolbar-btn { border: none; background: none; width: 28px; height: 28px; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #4b5563; cursor: pointer; }
    .toolbar-btn:hover { background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.05); color: #111827; }
    .toolbar-btn i { width: 14px; height: 14px; }

    /* Switch Toggle */
    .switch-toggle { position: relative; display: inline-block; width: 44px; height: 22px; }
    .switch-toggle input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e5e7eb; transition: .3s; border-radius: 22px; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    input:checked + .slider { background-color: #22c55e; }
    input:checked + .slider:before { transform: translateX(22px); }

    /* SMTP Redesign Styles */
    .settings-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 16px;
    }
    .settings-section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }
    .settings-section-header i { color: #4b5563; width: 20px; height: 20px; }
    .settings-section-header h3 { font-size: 15px; font-weight: 600; margin: 0; color: #111827; }
    .section-desc { font-size: 13px; color: #6b7280; margin: -15px 0 20px 0; }
    .field-hint { font-size: 12px; color: #6b7280; margin-top: 6px; display: block; line-height: 1.5; }
    .tax-formula-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
    }
    .tax-formula-summary {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 14px;
        margin-top: 18px;
    }
    .tax-formula-summary-card {
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
    }
    .tax-formula-summary-card.wide {
        background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
        border-color: #fed7aa;
    }
    .tax-formula-summary-card span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        margin-bottom: 6px;
    }
    .tax-formula-summary-card strong {
        display: block;
        color: #111827;
        font-size: 22px;
        line-height: 1.2;
        word-break: break-word;
    }
    .tax-builder-toolbar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }
    .formula-token-btn {
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 10px;
        padding: 8px 12px;
        color: #374151;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .formula-token-btn:hover {
        border-color: #fdba74;
        background: #fff7ed;
        color: #c2410c;
    }
    .formula-token-btn.operator {
        min-width: 42px;
        font-weight: 700;
        font-family: monospace;
    }
    .tax-variable-panel {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 16px;
        background: #fcfcfd;
    }
    .tax-variable-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }
    .tax-variable-panel h4 {
        margin: 0;
        color: #111827;
        font-size: 14px;
        font-weight: 600;
    }
    .tax-variable-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 10px;
    }
    .formula-token-btn.variable {
        text-align: left;
        padding: 12px;
    }
    .formula-token-btn.variable strong,
    .formula-token-btn.variable span {
        display: block;
    }
    .formula-token-btn.variable strong {
        font-size: 13px;
        margin-bottom: 4px;
    }
    .formula-token-btn.variable span {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.5;
    }
    .tax-slab-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .tax-slab-row {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    }
    .tax-slab-grid {
        display: grid;
        grid-template-columns: minmax(160px, 1.1fr) 110px 110px minmax(250px, 1.6fr) minmax(250px, 1.8fr);
        gap: 12px;
    }
    .tax-slab-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 12px;
    }
    .tax-example-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-top: 16px;
    }
    .tax-example-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 14px;
        background: #fff;
    }
    .tax-example-card strong {
        display: block;
        color: #111827;
        font-size: 13px;
        margin-bottom: 4px;
    }
    .tax-example-card span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        margin-bottom: 8px;
        line-height: 1.5;
    }
    .tax-example-card code {
        display: block;
        font-family: monospace;
        color: #1f2937;
        background: #f9fafb;
        border: 1px solid #eef2f7;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 12px;
        word-break: break-word;
    }
    .input-with-icon { position: relative; display: flex; align-items: center; }
    .input-with-icon input { padding-right: 40px; }
    .input-icon-btn { position: absolute; right: 12px; background: none; border: none; color: #9ca3af; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; }
    .input-icon-btn:hover { color: #4b5563; }
    .test-connection-row { display: flex; gap: 12px; align-items: center; }
    .test-connection-row input { flex: 1; }
    .modal-cancel-btn { 
        display: flex; 
        align-items: center; 
        gap: 8px; 
        padding: 6px 12px; 
        border: 1px solid #e5e7eb; 
        background: #fff; 
        border-radius: 6px; 
        font-size: 13px; 
        color: #4b5563; 
        cursor: pointer; 
        transition: all 0.2s;
    }
    .modal-cancel-btn:hover { background: #f9fafb; border-color: #d1d5db; }
    .modal-cancel-btn i { width: 14px; height: 14px; }

    @media (max-width: 900px) {
        .tax-formula-summary,
        .tax-slab-grid,
        .tax-variable-grid,
        .tax-example-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    function switchTab(tabId, btn) {
        document.querySelectorAll('.settings-tab-content').forEach(el => el.style.display = 'none');
        document.getElementById(tabId).style.display = 'block';
        document.querySelectorAll('.tab-item').forEach(b => b.classList.remove('active'));
        if (!btn) btn = document.querySelector(`.tab-item[onclick*="'${tabId}'"]`);
        if (btn) btn.classList.add('active');
        localStorage.setItem('settings_active_tab', tabId);
        if (window.lucide) window.lucide.createIcons();
    }

    window.addEventListener('DOMContentLoaded', function() {
        const activeTab = localStorage.getItem('settings_active_tab');
        if (activeTab && document.getElementById(activeTab)) {
            switchTab(activeTab);
        }
        syncTaxFormulaSummary();
    });

    // Bank Functions
    function openBankModal() {
        document.getElementById('bankModal').style.display = 'flex';
        document.getElementById('bankModalTitle').innerText = 'Add New Bank';
        document.getElementById('bankForm').reset();
        document.getElementById('bank_id').value = '';
    }
    function closeBankModal() { document.getElementById('bankModal').style.display = 'none'; }
    function editBank(id, name, code) {
        document.getElementById('bankModal').style.display = 'flex';
        document.getElementById('bankModalTitle').innerText = 'Edit Bank';
        document.getElementById('bank_id').value = id;
        document.getElementById('bank_name').value = name;
        document.getElementById('bank_code').value = code;
    }
    document.getElementById('bankForm').onsubmit = function(e) {
        e.preventDefault();
        const id = document.getElementById('bank_id').value;
        const url = id ? `/settings/banks/${id}` : '/settings/banks';
        const method = id ? 'PUT' : 'POST';
        const data = Object.fromEntries(new FormData(this).entries());
        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(data)
        }).then(res => res.json()).then(data => location.reload());
    }
    function deleteBank(id) {
        if (!confirm('Are you sure you want to delete this bank?')) return;
        fetch(`/settings/banks/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(res => res.json()).then(data => location.reload());
    }

    // SMTP Functions
    function togglePasswordVisibility(id) {
        const input = document.getElementById(id);
        const icon = document.getElementById('toggle_password_icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            input.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function updateSmtpPort(checkbox) {
        const portInput = document.getElementById('smtp_port');
        const encryptionInput = document.getElementById('smtp_encryption');
        if (checkbox.checked) {
            portInput.value = '465';
            encryptionInput.value = 'ssl';
        } else {
            portInput.value = '587';
            encryptionInput.value = 'tls';
        }
    }

    function openSmtpModal() {
        document.getElementById('smtpModal').style.display = 'flex';
        document.getElementById('smtpModalTitle').innerText = 'Add SMTP Configuration';
        document.getElementById('smtpForm').reset();
        document.getElementById('smtp_id').value = '';
        document.getElementById('modal_test_email').value = '';
        document.getElementById('smtp_use_ssl').checked = false;
        document.getElementById('smtp_encryption').value = 'tls';
        document.getElementById('smtp_password').type = 'password';
        if (window.lucide) window.lucide.createIcons();
    }
    
    function closeSmtpModal() { document.getElementById('smtpModal').style.display = 'none'; }
    
    function editSmtp(config) {
        document.getElementById('smtpModal').style.display = 'flex';
        document.getElementById('smtpModalTitle').innerText = 'Edit SMTP Configuration';
        document.getElementById('smtp_id').value = config.id;
        document.getElementById('smtp_name').value = config.name;
        document.getElementById('smtp_description').value = config.description || '';
        document.getElementById('smtp_host').value = config.host;
        document.getElementById('smtp_port').value = config.port;
        document.getElementById('smtp_username').value = config.username;
        // document.getElementById('smtp_password').value = config.password; // Optional: usually keep empty for security
        document.getElementById('smtp_from_email').value = config.from_email;
        document.getElementById('smtp_from_name').value = config.from_name;
        document.getElementById('smtp_is_default').checked = config.is_default;
        
        const useSsl = config.encryption === 'ssl' || config.port == 465;
        document.getElementById('smtp_use_ssl').checked = useSsl;
        document.getElementById('smtp_encryption').value = config.encryption || 'tls';
        
        document.getElementById('modal_test_email').value = '';
        document.getElementById('smtp_password').type = 'password';
        if (window.lucide) window.lucide.createIcons();
    }

    document.getElementById('smtpForm').onsubmit = function(e) {
        e.preventDefault();
        const id = document.getElementById('smtp_id').value;
        const url = id ? `/settings/smtp/${id}` : '/settings/smtp';
        const data = Object.fromEntries(new FormData(this).entries());
        data.is_default = document.getElementById('smtp_is_default').checked ? 1 : 0;
        fetch(url, {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(data)
        }).then(res => res.json()).then(data => location.reload());
    }

    function deleteSmtp(id) {
        if (!confirm('Are you sure you want to delete this SMTP configuration?')) return;
        fetch(`/settings/smtp/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(res => res.json()).then(data => location.reload());
    }

    function setDefaultSmtp(id) {
        fetch(`/settings/smtp/${id}/default`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(res => res.json()).then(data => location.reload());
    }

    function testSmtp(id) {
        const email = prompt("Enter recipient email for testing:", "test@example.com");
        if (!email) return;
        fetch('/settings/test-email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ email: email, smtp_id: id })
        }).then(res => res.json()).then(data => alert(data.message));
    }

    function testSmtpInModal() {
        const email = document.getElementById('modal_test_email').value;
        if (!email) return alert('Please enter a test recipient email');
        
        const id = document.getElementById('smtp_id').value;
        const formData = new FormData(document.getElementById('smtpForm'));
        const config = Object.fromEntries(formData.entries());

        fetch('/settings/test-email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ email: email, smtp_id: id, config: config })
        }).then(res => res.json()).then(data => alert(data.message));
    }

    function sendTestEmail() {
        const email = document.getElementById('test_recipient').value;
        if (!email) return alert('Please enter a recipient email');
        fetch('/settings/test-email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ email: email })
        }).then(res => res.json()).then(data => alert(data.message));
    }

    // Initialize Quill Editor
    let quill;
    function initQuill() {
        if (quill) return;
        
        quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Write your policy content here...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });

        // CSS for custom editor
        const style = document.createElement('style');
        style.textContent = `
            .ql-toolbar.ql-snow { border: none !important; border-bottom: 1px solid #e5e7eb !important; background: #f9fafb !important; }
            .ql-container.ql-snow { border: none !important; }
            .ql-editor { 
                font-family: 'Public Sans', sans-serif !important; 
                font-size: 14px !important; 
                color: #374151 !important; 
                min-height: 250px;
                padding: 16px !important;
                background: #fff !important;
            }
        `;
        document.head.append(style);
    }

    // Policy Functions
    function openPolicyModal() {
        document.getElementById('policyModal').style.display = 'flex';
        document.getElementById('policyModalTitle').innerText = 'Add Policy Section';
        document.getElementById('policySubmitBtn').innerText = 'Add Section';
        document.getElementById('policyForm').reset();
        document.getElementById('policy_id').value = '';
        
        initQuill();
        if (quill) {
            quill.setContents([]);
            setTimeout(() => quill.focus(), 100);
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    function closePolicyModal() { document.getElementById('policyModal').style.display = 'none'; }
    function editPolicy(policy) {
        document.getElementById('policyModal').style.display = 'flex';
        document.getElementById('policyModalTitle').innerText = 'Edit Policy Section';
        document.getElementById('policySubmitBtn').innerText = 'Save Changes';
        document.getElementById('policy_id').value = policy.id;
        document.getElementById('policy_title').value = policy.title;
        document.getElementById('policy_is_visible').checked = policy.is_visible;
        
        initQuill();
        if (quill) {
            quill.clipboard.dangerouslyPasteHTML(policy.content || '');
            setTimeout(() => quill.focus(), 100);
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    document.getElementById('policyForm').onsubmit = function(e) {
        e.preventDefault();
        const id = document.getElementById('policy_id').value;
        const data = Object.fromEntries(new FormData(this).entries());
        data.is_visible = document.getElementById('policy_is_visible').checked ? 1 : 0;
        
        // Populate hidden input with HTML content
        if (quill) {
            data.content = quill.root.innerHTML;
        }

        fetch(id ? `/settings/policies/${id}` : '/settings/policies', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(data)
        }).then(res => res.json()).then(data => location.reload());
    }
    function deletePolicy(id) {
        if (!confirm('Are you sure?')) return;
        fetch(`/settings/policies/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(res => res.json()).then(data => location.reload());
    }
    function togglePolicyVisibility(id) {
        fetch(`/settings/policies/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(res => res.json()).then(data => {
            // No reload needed for toggle, but visibility classes might need update
            // For now, reload for simplicity
            location.reload();
        });
    }
    function reorderPolicy(id, direction) {
        fetch(`/settings/policies/${id}/reorder`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ direction: direction })
        }).then(res => res.json()).then(data => location.reload());
    }

    // General Settings
    function updateGeneralSettings(type) {
        const data = {};
        if (type === 'id_prefix') data.employee_id_prefix = document.getElementById('id_prefix').value;
        if (type === 'hr_emails') data.hr_notification_emails = document.getElementById('hr_emails').value;

        fetch('/settings/general', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify(data)
        }).then(res => res.json()).then(data => {
            if (data.success) {
                syncEmployeeIdPreview(data);
                alert(data.message);
            }
        });
    }

    function resetEmployeeIdCounter() {
        if (!confirm('Reset the employee ID counter back to 0?')) return;

        fetch('/settings/general', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ reset_employee_id_counter: true })
        }).then(res => res.json()).then(data => {
            if (data.success) {
                syncEmployeeIdPreview(data);
                alert('Employee ID counter reset successfully.');
            }
        });
    }

    function syncEmployeeIdPreview(data) {
        if (!data) return;

        if (data.employeeIdPrefix) {
            document.getElementById('id_prefix').value = data.employeeIdPrefix;
            document.getElementById('employee_id_hint_one').textContent = `${data.employeeIdPrefix}001`;
            document.getElementById('employee_id_hint_two').textContent = `${data.employeeIdPrefix}002`;
        }

        if (typeof data.employeeIdCounter !== 'undefined') {
            document.getElementById('employee_id_current_number').textContent = data.employeeIdCounter;
        }

        if (data.nextEmployeeId) {
            document.getElementById('employee_id_next').textContent = data.nextEmployeeId;
        }
    }

    function createTaxSlabRow(slab = {}) {
        return `
            <div class="tax-slab-row">
                <div class="tax-slab-grid">
                    <div class="form-group" style="margin: 0;">
                        <label>Label</label>
                        <input type="text" class="form-control tax-slab-label" value="${slab.label || ''}" placeholder="e.g. Above 100,000" style="background: #f9fafb;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Min</label>
                        <input type="number" step="0.01" class="form-control tax-slab-min" value="${slab.min ?? 0}" style="background: #f9fafb;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Max</label>
                        <input type="number" step="0.01" class="form-control tax-slab-max" value="${slab.max ?? ''}" placeholder="Leave blank for open ended" style="background: #f9fafb;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Taxable Income Formula</label>
                        <input type="text" class="form-control tax-slab-taxable-formula formula-builder-target" value="${slab.taxable_income_formula || ''}" placeholder="Leave blank to use default taxable formula" style="background: #f9fafb; font-family: monospace;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Tax Formula</label>
                        <input type="text" class="form-control tax-slab-formula formula-builder-target" value="${slab.formula || ''}" placeholder="e.g. (taxable_income - 50000) * 0.01" style="background: #f9fafb; font-family: monospace;">
                    </div>
                </div>
                <div class="tax-slab-actions">
                    <button type="button" class="btn btn-outline" onclick="removeTaxSlabRow(this)">
                        <i data-lucide="trash-2"></i> Remove
                    </button>
                </div>
            </div>
        `;
    }

    function addTaxSlabRow(slab = null) {
        const list = document.getElementById('taxSlabsList');
        list.insertAdjacentHTML('beforeend', createTaxSlabRow(slab || {
            label: `Slab ${list.children.length + 1}`,
            min: 0,
            max: '',
            formula: '0'
        }));
        syncTaxFormulaSummary();
        if (window.lucide) window.lucide.createIcons();
    }

    function removeTaxSlabRow(button) {
        const list = document.getElementById('taxSlabsList');
        if (list.children.length === 1) {
            return alert('At least one tax slab is required.');
        }

        button.closest('.tax-slab-row').remove();
        syncTaxFormulaSummary();
    }

    function insertFormulaToken(token) {
        const focused = document.activeElement && document.activeElement.classList.contains('formula-builder-target')
            ? document.activeElement
            : document.getElementById('taxable_income_formula');

        const start = focused.selectionStart ?? focused.value.length;
        const end = focused.selectionEnd ?? focused.value.length;
        const needsSpacing = /[a-zA-Z0-9_)]$/.test(focused.value.slice(0, start)) && /[a-zA-Z_(]/.test(token);
        const insertion = needsSpacing ? ` ${token}` : token;

        focused.value = focused.value.slice(0, start) + insertion + focused.value.slice(end);
        const cursor = start + insertion.length;
        focused.focus();
        focused.setSelectionRange(cursor, cursor);

        if (focused.id === 'taxable_income_formula') {
            syncTaxFormulaSummary();
        }
    }

    function syncTaxFormulaSummary() {
        document.getElementById('taxSlabCount').textContent = document.querySelectorAll('#taxSlabsList .tax-slab-row').length;
        document.getElementById('taxFormulaPreview').textContent = document.getElementById('taxable_income_formula').value || 'No formula set';
    }

    function collectTaxFormulaPayload() {
        return {
            taxable_income_formula: document.getElementById('taxable_income_formula').value.trim(),
            slabs: Array.from(document.querySelectorAll('#taxSlabsList .tax-slab-row')).map(row => ({
                label: row.querySelector('.tax-slab-label').value.trim(),
                min: row.querySelector('.tax-slab-min').value,
                max: row.querySelector('.tax-slab-max').value,
                taxable_income_formula: row.querySelector('.tax-slab-taxable-formula').value.trim(),
                formula: row.querySelector('.tax-slab-formula').value.trim()
            }))
        };
    }

    function saveTaxFormulas() {
        fetch('{{ route("settings.tax-formulas.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(collectTaxFormulaPayload())
        })
        .then(async response => {
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (payload.errors) {
                    const message = Object.values(payload.errors).flat().join('\n');
                    throw new Error(message);
                }

                throw new Error(payload.message || 'Failed to save tax formulas.');
            }

            return payload;
        })
        .then(data => {
            syncTaxFormulaSummary();
            showToast('Success', data.message, 'success');
        })
        .catch(error => {
            showToast('Error', error.message, 'error');
        });
    }

    function filterTaxVariables() {
        const query = (document.getElementById('taxVariableSearch')?.value || '').trim().toLowerCase();

        document.querySelectorAll('[data-variable-search]').forEach(button => {
            button.style.display = button.dataset.variableSearch.includes(query) ? '' : 'none';
        });
    }

    function loadTaxFormulaExample() {
        const input = document.getElementById('taxExampleEmployeeSearch');
        const option = Array.from(document.querySelectorAll('#taxExampleEmployees option'))
            .find(item => item.value === input.value);

        if (!option) {
            return alert('Select an employee from the list to load example values.');
        }

        fetch(`{{ route('settings.tax-formulas.example') }}?employee_id=${option.dataset.id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Failed to load formula example.');
            }

            return payload;
        })
        .then(payload => {
            document.getElementById('taxExampleState').innerHTML = `
                <strong style="display:block;color:#111827;margin-bottom:4px;">${payload.employee.name} (${payload.employee.employee_id})</strong>
                <span style="color:#6b7280;font-size:13px;">Using current payroll inputs for ${payload.month}.</span>
            `;

            const grid = document.getElementById('taxExampleGrid');
            grid.innerHTML = payload.variables.map(variable => `
                <div class="tax-example-card">
                    <strong>${variable.label}</strong>
                    <span>${variable.description}</span>
                    <code>${Number(variable.value).toFixed(2)}</code>
                </div>
            `).join('');
            grid.style.display = 'grid';
        })
        .catch(error => {
            showToast('Error', error.message, 'error');
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            if (typeof closeBankModal === 'function') closeBankModal();
            if (typeof closeSmtpModal === 'function') closeSmtpModal();
            if (typeof closePolicyModal === 'function') closePolicyModal();
        }
    });

    function updateSystemValues() {
        const officeLocation = document.getElementById('office_location').value;
        const hrContact = document.getElementById('hr_contact').value;
        
        fetch('{{ route("settings.general.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                office_location: officeLocation,
                hr_contact: hrContact
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message, 'success');
            } else {
                showToast('Error', data.message || 'Failed to update system values', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error', 'An unexpected error occurred', 'error');
        });
    }

    // Reuse existing toast functionality or implement a simple one if needed
    function showToast(title, message, type) {
        if (window.toast) {
            window.toast(title, message, type);
        } else {
            alert(title + ': ' + message);
        }
    }

    document.getElementById('taxable_income_formula')?.addEventListener('input', syncTaxFormulaSummary);
    document.getElementById('taxVariableSearch')?.addEventListener('input', filterTaxVariables);
</script>
@endsection
