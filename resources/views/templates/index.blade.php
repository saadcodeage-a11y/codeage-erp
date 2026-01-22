@extends('layouts.app')

@section('title', 'Templates & Forms')

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1>Templates & Forms</h1>
        <p>Manage email templates and employee forms</p>
    </div>
    <div class="header-right" style="display: flex; align-items: flex-end; flex-direction: column; gap: 4px;">
        <div style="font-size: 11px; color: #6b7280; text-align: right;">
            Total Templates:<br>
            HR: {{ $counts['hr'] }} | Accounts: {{ $counts['accounts'] }} | General: {{ $counts['general'] }}
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs-container" style="margin-bottom: 24px;">
    <a href="{{ route('templates.index', ['tab' => 'hr']) }}" class="tab-item {{ $activeTab == 'hr' ? 'active' : '' }}">
        HR Templates <span class="badge" style="background: {{ $activeTab == 'hr' ? '#ff4d00' : '#f3f4f6' }}; color: {{ $activeTab == 'hr' ? 'white' : '#6b7280' }};">{{ $counts['hr'] }}</span>
    </a>
    <a href="{{ route('templates.index', ['tab' => 'accounts']) }}" class="tab-item {{ $activeTab == 'accounts' ? 'active' : '' }}">
        Accounts Templates <span class="badge" style="background: {{ $activeTab == 'accounts' ? '#ff4d00' : '#f3f4f6' }}; color: {{ $activeTab == 'accounts' ? 'white' : '#6b7280' }};">{{ $counts['accounts'] }}</span>
    </a>
    <a href="{{ route('templates.index', ['tab' => 'general']) }}" class="tab-item {{ $activeTab == 'general' ? 'active' : '' }}">
        General Templates <span class="badge" style="background: {{ $activeTab == 'general' ? '#ff4d00' : '#f3f4f6' }}; color: {{ $activeTab == 'general' ? 'white' : '#6b7280' }};">{{ $counts['general'] }}</span>
    </a>
    <a href="{{ route('templates.index', ['tab' => 'forms']) }}" class="tab-item {{ $activeTab == 'forms' ? 'active' : '' }}">
        Forms <span class="badge" style="background: {{ $activeTab == 'forms' ? '#ff4d00' : '#f3f4f6' }}; color: {{ $activeTab == 'forms' ? 'white' : '#6b7280' }};">{{ $counts['forms'] }}</span>
    </a>
</div>

<!-- Search and Action -->
<div class="search-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: white; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb;">
    <form method="GET" action="{{ route('templates.index') }}" style="flex: 1; position: relative;">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #9ca3af;"></i>
        <input type="text" name="search" placeholder="Search {{ $activeTab }} templates..." value="{{ request('search') }}" style="width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #f3f4f6; border-radius: 8px; font-size: 14px; background: #f9fafb;">
    </form>
    <div style="margin-left: 16px;">
        @if($activeTab == 'forms')
            <button class="btn btn-primary" onclick="alert('New Form Coming Soon')">
                <i data-lucide="plus"></i> New Form
            </button>
        @else
            <button class="btn btn-primary" onclick="openCreateEmailTemplateModal('{{ $activeTab }}')">
                <i data-lucide="plus"></i> New {{ strtoupper($activeTab) }} Template
            </button>
        @endif
    </div>
</div>

@if($activeTab == 'forms')
    <!-- Forms Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
        @forelse($templates as $form)
            <div class="table-card" style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div style="background: #fff1f2; padding: 10px; border-radius: 8px;">
                            <i data-lucide="file-text" style="width: 20px; height: 20px; color: #f43f5e;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 15px; font-weight: 600; color: #111827;">{{ $form->name }}</h3>
                            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                <label class="switch-sm">
                                    <input type="checkbox" {{ $form->is_active ? 'checked' : '' }} onchange="toggleTemplateStatus({{ $form->id }}, 'form')">
                                    <span class="slider-sm round"></span>
                                </label>
                                <span class="status-badge {{ $form->is_active ? 'active' : 'inactive' }}" id="status-form-{{ $form->id }}" style="font-size: 11px; padding: 2px 8px;">
                                    {{ $form->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.5;">{{ $form->description }}</p>
                <div style="display: flex; gap: 16px; padding: 12px 0; border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6;">
                    <div style="font-size: 12px;">
                        <span style="color: #6b7280;">Fields:</span>
                        <span style="font-weight: 600; color: #111827; margin-left: 4px;">{{ $form->fields_count }} fields</span>
                    </div>
                    <div style="font-size: 12px;">
                        <span style="color: #6b7280;">Required:</span>
                        <span style="font-weight: 600; color: #111827; margin-left: 4px;">{{ $form->required_count }} required</span>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <a href="{{ route('onboarding.show', 'preview') }}" target="_blank" class="btn btn-outline" style="padding: 6px; font-size: 12px; height: 32px; display: flex; align-items: center; justify-content: center; text-decoration: none;"><i data-lucide="eye" style="width: 14px; height: 14px; margin-right: 4px;"></i> Preview</a>
                    <button class="btn btn-outline" onclick="openEditFormTemplateModal({{ $form->id }}, '{{ addslashes($form->name) }}', {{ $form->smtp_config_id ?? 'null' }}, {{ $form->is_active ? 'true' : 'false' }})" style="padding: 6px; font-size: 12px; height: 32px;"><i data-lucide="edit-3" style="width: 14px; height: 14px; margin-right: 4px;"></i> Edit</button>
                </div>
            </div>
        @empty
            <div class="table-card" style="grid-column: 1 / -1; padding: 60px; text-align: center;">
                <div style="background: #f9fafb; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i data-lucide="file-question" style="width: 32px; height: 32px; color: #9ca3af;"></i>
                </div>
                <h3 style="margin: 0; color: #374151;">No forms found</h3>
                <p style="color: #6b7280; font-size: 14px;">Try adjusting your search or create a new form.</p>
            </div>
        @endforelse
    </div>
@else
    <!-- Templates Table -->
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Template Name</th>
                    <th>Status</th>
                    <th>SMTP Config</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="background: #fff7ed; padding: 8px; border-radius: 8px;">
                                    <i data-lucide="mail" style="width: 18px; height: 18px; color: #f97316;"></i>
                                </div>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 600; color: #111827; font-size: 14px;">{{ $template->name }}</span>
                                    <span style="color: #6b7280; font-size: 12px;">{{ Str::limit($template->description, 50) }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <label class="switch-sm">
                                    <input type="checkbox" {{ $template->is_active ? 'checked' : '' }} onchange="toggleTemplateStatus({{ $template->id }}, 'template')">
                                    <span class="slider-sm round"></span>
                                </label>
                                <span class="status-badge {{ $template->is_active ? 'active' : 'inactive' }}" id="status-template-{{ $template->id }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </td>
                        <td>
                            @if($template->smtp_config_id)
                                <span style="font-size: 13px; color: #374151;">Custom Config</span>
                            @else
                                <span style="font-size: 13px; color: #9ca3af; font-style: italic;">Not configured</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                <button class="btn-action outline" title="View" onclick="viewTemplate({{ $template->id }})"><i data-lucide="eye" style="width: 14px; height: 14px;"></i> View</button>
                                <button class="btn-action outline" title="Edit" onclick="editTemplate({{ $template->id }})"><i data-lucide="edit-2" style="width: 14px; height: 14px;"></i> Edit</button>
                                <button class="btn-action outline" title="Duplicate"><i data-lucide="copy" style="width: 14px; height: 14px;"></i></button>
                                <button class="btn-action outline text-danger" title="Delete"><i data-lucide="trash-2" style="width: 14px; height: 14px;"></i></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding: 80px 0; text-align: center;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <i data-lucide="mail-warning" style="width: 48px; height: 48px; color: #cbd5e1;"></i>
                                <div style="font-size: 16px; font-weight: 500; color: #64748b;">No templates found</div>
                                <p style="font-size: 14px; color: #94a3b8; margin: 0;">There are no templates in the {{ $activeTab }} category.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

<!-- Template Data Store -->
<script>
    const templateData = {
        @foreach($templates as $template)
        @if(!($activeTab == 'forms'))
        {{ $template->id }}: {
            id: {{ $template->id }},
            category: "{{ $template->category }}",
            name: @json($template->name),
            subject: @json($template->subject),
            body: @json($template->body),
            variables: @json($template->variables ?? ''),
            smtp_config_id: {{ $template->smtp_config_id ?? 'null' }},
            is_active: {{ $template->is_active ? 'true' : 'false' }}
        },
        @endif
        @endforeach
    };
</script>

<style>
    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 6px;
    }
    
    .switch-sm {
        position: relative;
        display: inline-block;
        width: 32px;
        height: 18px;
    }

    .switch-sm input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider-sm {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #e5e7eb;
        transition: .4s;
    }

    .slider-sm:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 2px;
        bottom: 2px;
        background-color: white;
        transition: .4s;
    }

    input:checked + .slider-sm {
        background-color: #ff4d00;
    }

    input:checked + .slider-sm:before {
        transform: translateX(14px);
    }

    .slider-sm.round {
        border-radius: 18px;
    }

    .slider-sm.round:before {
        border-radius: 50%;
    }

    .text-danger {
        color: #ef4444 !important;
        border-color: #fecaca !important;
    }
    
    .text-danger:hover {
        background: #fef2f2 !important;
    }
</style>

<!-- Create Email Template Modal -->
<div id="createEmailTemplateModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 600px; width: 90%;">
        <div class="modal-header">
            <div>
                <h2 id="modalTitle">Create Email Template</h2>
                <p class="modal-desc">Configure email template settings, SMTP, and customize the email content with HTML.</p>
            </div>
            <button onclick="closeCreateEmailTemplateModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <form id="createEmailTemplateForm" style="display: flex; flex-direction: column; gap: 20px;">
                @csrf
                <input type="hidden" name="category" id="templateCategory">
                
                <div class="form-group">
                    <label>Template Name</label>
                    <input type="text" name="name" placeholder="e.g., Employee Invitation" required style="border: 2px solid #e5e7eb;">
                </div>

                <div class="form-group">
                    <label>Email Subject</label>
                    <input type="text" name="subject" placeholder="e.g., Welcome to CodeAge" required style="background: #f9fafb;">
                    <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                        You can use variables like 
                        @foreach(array_slice($systemVariables, 0, 1) as $var => $desc)
                            @php $placeholder = '{{' . $var . '}}'; @endphp
                            <span style="color: #ff4d00; cursor: pointer;" onclick="insertVariable('subject', '{{ $placeholder }}')">{{ $placeholder }}</span>
                        @endforeach
                    </small>
                </div>

                <div class="form-group">
                    <label>SMTP Configuration</label>
                    <div class="custom-select-wrapper" id="smtpSelectWrapper">
                        <div class="custom-select-display" onclick="toggleCustomSelect(this)">
                            <span class="selected-value">Select SMTP Configuration</span>
                            <i data-lucide="chevron-down"></i>
                        </div>
                        <div class="custom-select-options" style="display: none;">
                            <div class="option" onclick="selectSmtpOption(this, '')">
                                <span>Default SMTP</span>
                            </div>
                            @foreach($smtpConfigs as $config)
                                <div class="option" onclick="selectSmtpOption(this, '{{ $config->id }}')" data-email="{{ $config->from_email }}">
                                    <span>{{ $config->name }} ({{ $config->from_email }})</span>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="smtp_config_id" class="custom-select-input">
                    </div>
                    <small id="smtpHint" style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">Emails will be sent from default configuration.</small>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 8px;" id="modeButtons">
                    <button type="button" class="btn btn-primary" id="btnEditHtml" onclick="showEditMode()" style="background: #ff4d00; border: none; font-size: 13px; padding: 6px 16px;">
                        <i data-lucide="code-2" style="width: 14px; height: 14px; margin-right: 6px;"></i> Edit HTML
                    </button>
                    <button type="button" class="btn btn-outline" id="btnPreviewHtml" onclick="showPreviewMode()" style="font-size: 13px; padding: 6px 16px;">
                        <i data-lucide="eye" style="width: 14px; height: 14px; margin-right: 6px;"></i> Preview
                    </button>
                    <div style="flex: 1; text-align: right;">
                        <span style="background: #f3f4f6; color: #4b5563; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; border: 1px solid #e5e7eb;">
                            Available Variables: <span id="variableCount">None</span>
                        </span>
                    </div>
                </div>

                <div class="form-group" id="editorContainer">
                    <label>Email Body</label>
                    <textarea name="body" id="templateBody" placeholder="Enter HTML email template..." style="width: 100%; height: 200px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-family: monospace; font-size: 13px; background: #f9fafb;"></textarea>
                    <div id="previewContainer" style="display: none; width: 100%; min-height: 200px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; overflow-y: auto;"></div>
                    <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px;">
                        <span style="font-size: 12px; color: #6b7280;">Quick Add:</span>
                        @foreach($systemVariables as $var => $desc)
                            @php $placeholder = '{{' . $var . '}}'; @endphp
                            <span class="var-pill" title="{{ $desc }}" onclick="insertVariable('body', '{{ $placeholder }}')">{{ $placeholder }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label>Add Variables (comma-separated)</label>
                    <input type="text" name="variables" id="templateVariables" placeholder="e.g., @{{employeeName}}, @{{formLink}}, @{{date}}" style="background: #f9fafb;" oninput="updateVariableBadge(this.value)">
                    <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">Define which variables can be used in this template</small>
                </div>

                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0; font-size: 15px; font-weight: 600; color: #111827;">Enable Template</h4>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">When enabled, this template will be used for automated emails</p>
                    </div>
                    <label class="switch-sm modal-toggle-wrapper">
                        <input type="checkbox" name="is_active" checked>
                        <span class="slider-sm round"></span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="padding: 20px 24px; background: #f9fafb; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <button onclick="closeCreateEmailTemplateModal()" class="btn btn-outline" style="padding: 10px 24px;">Cancel</button>
            <button type="submit" form="createEmailTemplateForm" class="btn btn-primary" style="padding: 10px 24px; background: #fca5a5; border: none;">Save Template</button>
        </div>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .modal-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        animation: modalScale 0.2s ease-out;
    }
    
    @keyframes modalScale {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .custom-select-wrapper {
        position: relative;
        cursor: pointer;
    }
    
    .custom-select-display {
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
    }
    
    .custom-select-options {
        position: absolute;
        top: 100%; left: 0; width: 100%;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-top: 4px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        z-index: 50;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .option {
        padding: 10px 12px;
        font-size: 14px;
        color: #374151;
    }
    
    .option:hover {
        background: #f3f4f6;
    }

    /* Override slider-sm for larger toggle in modal */
    .modal-toggle-wrapper {
        width: 52px !important;
        height: 31px !important;
    }

    .modal-toggle-wrapper .slider-sm {
        background-color: #f3f4f6;
        border: 2px solid #e5e7eb;
    }

    .modal-toggle-wrapper input:checked + .slider-sm {
        background-color: #ff4d00;
        border-color: #ff4d00;
    }

    .modal-toggle-wrapper .slider-sm:before {
        height: 23px;
        width: 23px;
        left: 2px;
        bottom: 2px;
    }

    .modal-toggle-wrapper input:checked + .slider-sm:before {
        transform: translateX(21px);
    }

    .var-pill {
        background: #fff7ed;
        color: #f97316;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        cursor: pointer;
        border: 1px solid #ffedd5;
        transition: all 0.2s;
    }

    .var-pill:hover {
        background: #ffedd5;
        border-color: #fed7aa;
        transform: translateY(-1px);
    }
</style>

<script>
    function openCreateEmailTemplateModal(category) {
        document.getElementById('templateCategory').value = category;
        document.getElementById('modalTitle').innerText = `Create ${category.toUpperCase()} Email Template`;
        document.getElementById('createEmailTemplateModal').style.display = 'flex';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeCreateEmailTemplateModal() {
        document.getElementById('createEmailTemplateModal').style.display = 'none';
        document.getElementById('createEmailTemplateForm').reset();
        document.getElementById('variableCount').innerText = 'None';
        document.getElementById('smtpHint').innerText = 'Emails will be sent from default configuration.';
    }

    function toggleCustomSelect(el) {
        const options = el.nextElementSibling;
        options.style.display = options.style.display === 'none' ? 'block' : 'none';
    }

    function selectSmtpOption(el, id) {
        const wrapper = el.closest('.custom-select-wrapper');
        const display = wrapper.querySelector('.selected-value');
        const input = wrapper.querySelector('.custom-select-input');
        const options = wrapper.querySelector('.custom-select-options');
        
        display.innerText = el.innerText;
        input.value = id;
        options.style.display = 'none';
        
        const hint = document.getElementById('smtpHint');
        const email = el.dataset.email;
        if (email) {
            hint.innerText = `Emails will be sent from: ${email}`;
        } else {
            hint.innerText = 'Emails will be sent from default configuration.';
        }
    }

    function updateVariableBadge(value) {
        const count = value.trim() ? value.split(',').length : 0;
        document.getElementById('variableCount').innerText = count > 0 ? `${count} Variables` : 'None';
    }

    function insertVariable(target, variable) {
        const input = target === 'subject' ? document.getElementsByName('subject')[0] : document.getElementById('templateBody');
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const text = input.value;
        input.value = text.substring(0, start) + variable + text.substring(end);
        input.focus();
        input.selectionStart = input.selectionEnd = start + variable.length;
        
        // Update preview if in preview mode
        if (document.getElementById('previewContainer').style.display !== 'none') {
            updatePreviewContent();
        }
    }

    function showEditMode() {
        document.getElementById('templateBody').style.display = 'block';
        document.getElementById('previewContainer').style.display = 'none';
        
        const btnEdit = document.getElementById('btnEditHtml');
        const btnPreview = document.getElementById('btnPreviewHtml');
        
        btnEdit.classList.remove('btn-outline');
        btnEdit.classList.add('btn-primary');
        btnEdit.style.background = '#ff4d00';
        btnEdit.style.color = 'white';
        btnEdit.style.borderColor = '#ff4d00';
        
        btnPreview.classList.remove('btn-primary');
        btnPreview.classList.add('btn-outline');
        btnPreview.style.background = 'white';
        btnPreview.style.color = '#374151';
        btnPreview.style.borderColor = '#e5e7eb';
    }

    function showPreviewMode() {
        const body = document.getElementById('templateBody');
        const preview = document.getElementById('previewContainer');
        
        body.style.display = 'none';
        preview.style.display = 'block';
        
        updatePreviewContent();
        
        const btnEdit = document.getElementById('btnEditHtml');
        const btnPreview = document.getElementById('btnPreviewHtml');
        
        btnEdit.classList.remove('btn-primary');
        btnEdit.classList.add('btn-outline');
        btnEdit.style.background = 'white';
        btnEdit.style.color = '#374151';
        btnEdit.style.borderColor = '#e5e7eb';
        
        btnPreview.classList.remove('btn-outline');
        btnPreview.classList.add('btn-primary');
        btnPreview.style.background = '#ff4d00';
        btnPreview.style.color = 'white';
        btnPreview.style.borderColor = '#ff4d00';
    }

    function updatePreviewContent() {
        const body = document.getElementById('templateBody').value;
        const preview = document.getElementById('previewContainer');
        
        // Simple variable replacement for preview
        let rendered = body;
        rendered = rendered.replace(/\{\{\s*employeeName\s*\}\}/g, '<strong>John Doe</strong>');
        rendered = rendered.replace(/\{\{\s*formLink\s*\}\}/g, '<a href="#">Click Here</a>');
        rendered = rendered.replace(/\{\{\s*date\s*\}\}/g, new Date().toLocaleDateString());
        
        preview.innerHTML = rendered || '<em style="color: #9ca3af;">No content to preview...</em>';
    }

    document.getElementById('createEmailTemplateForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.is_active = this.is_active.checked ? 1 : 0;

        fetch('{{ route('templates.email.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Creation failed');
            return data;
        })
        .then(data => {
            sessionStorage.setItem('flash_message', data.message);
            sessionStorage.setItem('flash_type', 'success');
            location.reload();
        })
        .catch(err => showToast(err.message, 'error'));
    }

    // Close on click outside
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeCreateEmailTemplateModal();
        }
        if (!e.target.closest('.custom-select-wrapper')) {
            document.querySelectorAll('.custom-select-options').forEach(opt => opt.style.display = 'none');
        }
    });

    function toggleTemplateStatus(id, type) {
        fetch(`./templates/${id}/${type}/toggle`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById(`status-${type}-${id}`);
                if (data.is_active) {
                    badge.classList.remove('inactive');
                    badge.classList.add('active');
                    badge.innerText = 'Active';
                } else {
                    badge.classList.remove('active');
                    badge.classList.add('inactive');
                    badge.innerText = 'Inactive';
                }
            }
        })
        .catch(err => showToast('Failed to toggle status', 'error'));
    }

    if (window.lucide) window.lucide.createIcons();

    // Wrapper functions that read from templateData object
    function viewTemplate(id) {
        const t = templateData[id];
        if (t) {
            openViewTemplateModal(t.id, t.name, t.subject, t.body);
        }
    }

    function editTemplate(id) {
        const t = templateData[id];
        if (t) {
            openEditTemplateModal(t.id, t.category, t.name, t.subject, t.body, t.variables, t.smtp_config_id, t.is_active);
        }
    }

    // View Template Modal
    function openViewTemplateModal(id, name, subject, body) {
        document.getElementById('viewTemplateName').innerText = name;
        document.getElementById('viewTemplateSubject').innerText = subject;
        document.getElementById('viewTemplateBody').innerHTML = body;
        document.getElementById('viewTemplateModal').style.display = 'flex';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeViewTemplateModal() {
        document.getElementById('viewTemplateModal').style.display = 'none';
    }

    // Edit Template Modal (reuse create modal)
    let editingTemplateId = null;

    function openEditTemplateModal(id, category, name, subject, body, variables, smtpConfigId, isActive) {
        editingTemplateId = id;
        document.getElementById('templateCategory').value = category;
        document.getElementById('modalTitle').innerText = `Edit ${category.toUpperCase()} Email Template`;
        document.getElementsByName('name')[0].value = name;
        document.getElementsByName('subject')[0].value = subject;
        document.getElementById('templateBody').value = body;
        document.getElementById('templateVariables').value = variables || '';
        updateVariableBadge(variables || '');
        
        // Set SMTP config
        if (smtpConfigId) {
            document.querySelector('.custom-select-input').value = smtpConfigId;
            const options = document.querySelectorAll('.option');
            options.forEach(opt => {
                if (opt.getAttribute('onclick')?.includes(smtpConfigId)) {
                    document.querySelector('.selected-value').innerText = opt.innerText;
                }
            });
        }
        
        // Set active toggle
        document.querySelector('input[name="is_active"]').checked = isActive;
        
        document.getElementById('createEmailTemplateModal').style.display = 'flex';
        if (window.lucide) window.lucide.createIcons();
    }

    // Override form submit to handle edit mode
    const originalSubmit = document.getElementById('createEmailTemplateForm').onsubmit;
    document.getElementById('createEmailTemplateForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.is_active = this.is_active.checked ? 1 : 0;

        const url = editingTemplateId 
            ? `{{ url('templates') }}/${editingTemplateId}/update`
            : '{{ route('templates.email.store') }}';
        const method = editingTemplateId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Operation failed');
            return data;
        })
        .then(data => {
            // Set session storage for toast on reload
            sessionStorage.setItem('flash_message', data.message);
            sessionStorage.setItem('flash_type', 'success');
            
            editingTemplateId = null;
            location.reload();
        })
        .catch(err => showToast(err.message, 'error'));
    };

    // Reset editing state when closing modal
    const originalClose = closeCreateEmailTemplateModal;
    closeCreateEmailTemplateModal = function() {
        editingTemplateId = null;
        document.getElementById('modalTitle').innerText = 'Create Email Template';
        originalClose();
    };
</script>

<!-- View Template Modal -->
<div id="viewTemplateModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 700px; width: 90%;">
        <div class="modal-header">
            <div>
                <h2 id="viewTemplateName">Template Name</h2>
                <p class="modal-desc" id="viewTemplateSubject">Subject line here</p>
            </div>
            <button onclick="closeViewTemplateModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 0; max-height: 500px; overflow-y: auto;">
            <div id="viewTemplateBody" style="padding: 24px; background: white;"></div>
        </div>
        <div class="modal-footer" style="padding: 16px 24px; background: #f9fafb; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <button onclick="closeViewTemplateModal()" class="btn btn-outline" style="padding: 8px 20px;">Close</button>
        </div>
    </div>
</div>
@endsection

<!-- Edit Form Template Modal -->
<div id="editFormTemplateModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 500px; width: 90%;">
        <div class="modal-header">
            <div>
                <h2 id="editFormModalTitle">Edit Form Template</h2>
                <p class="modal-desc">Update form template details</p>
            </div>
            <button onclick="closeEditFormTemplateModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <form id="editFormTemplateForm" style="display: flex; flex-direction: column; gap: 20px;">
                @csrf
                <div class="form-group">
                    <label>Form Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="name" id="editFormName" required placeholder="e.g. Employee Onboarding Form" style="border: 2px solid #e5e7eb;">
                </div>
                
                <div class="form-group">
                    <label>SMTP Configuration</label>
                    <div class="custom-select-wrapper" id="editFormSmtpSelect">
                        <div class="custom-select-display" onclick="toggleCustomSelect(this)">
                            <span class="selected-value">Default (System Config)</span>
                            <input type="hidden" name="smtp_config_id" id="editFormSmtpInput">
                            <i data-lucide="chevron-down" style="width: 16px; height: 16px; color: #9ca3af;"></i>
                        </div>
                        <div class="custom-select-options" style="display: none;">
                            <div class="option" onclick="selectEditFormSmtpOption(this, null)">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;">Default (System Config)</span>
                                </div>
                            </div>
                            @foreach($smtpConfigs as $config)
                                <div class="option" onclick="selectEditFormSmtpOption(this, {{ $config->id }})">
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 500;">{{ $config->host }}</span>
                                        <span style="font-size: 11px; color: #6b7280;">{{ $config->username }}</span>
                                    </div>
                                    @if($config->id == ($templates[0]->smtp_config_id ?? null))
                                        <!-- Check logic handled in JS -->
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0; font-size: 15px; font-weight: 600; color: #111827;">Enable Form</h4>
                        <p style="margin: 4px 0 0 0; font-size: 13px; color: #6b7280;">When enabled, this form will be accessible publicly</p>
                    </div>
                    <label class="switch-sm modal-toggle-wrapper">
                        <input type="checkbox" name="is_active" id="editFormActive">
                        <span class="slider-sm round"></span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="padding: 20px 24px; background: #f9fafb; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
            <button onclick="closeEditFormTemplateModal()" class="btn btn-outline" style="padding: 10px 24px;">Cancel</button>
            <button type="submit" form="editFormTemplateForm" class="btn btn-primary" style="padding: 10px 24px; background: #f97316; border-color: #f97316;">Update Form</button>
        </div>
    </div>
</div>

<script>
    let editingFormId = null;

    function openEditFormTemplateModal(id, name, smtpConfigId, isActive) {
        editingFormId = id;
        document.getElementById('editFormName').value = name;
        document.getElementById('editFormActive').checked = isActive;
        
        // precise SMTP selection
        const wrapper = document.getElementById('editFormSmtpSelect');
        const display = wrapper.querySelector('.selected-value');
        const input = document.getElementById('editFormSmtpInput');
        
        input.value = smtpConfigId || '';
        
        // Find label
        // Default
        if (!smtpConfigId) {
            display.innerText = 'Default (System Config)';
        } else {
             // Find in options by checking onclick handlers? Or text content.
             // Simpler: loop through options in the wrapper
             const options = wrapper.querySelectorAll('.option');
             let found = false;
             options.forEach(opt => {
                 if (opt.getAttribute('onclick').includes(`, ${smtpConfigId})`)) {
                     const text = opt.querySelector('span').innerText; // Host
                     const sub = opt.querySelector('div div span:nth-child(2)')?.innerText; // Username
                     const label = sub ? `${text} (${sub})` : text;
                     display.innerText = label;
                     found = true;
                 }
             });
             if (!found) display.innerText = 'Default (System Config)';
        }

        document.getElementById('editFormTemplateModal').style.display = 'flex';
        if (window.lucide) window.lucide.createIcons();
    }
    
    function closeEditFormTemplateModal() {
        document.getElementById('editFormTemplateModal').style.display = 'none';
        editingFormId = null;
    }

    function selectEditFormSmtpOption(el, value) {
        const wrapper = document.getElementById('editFormSmtpSelect');
        const input = document.getElementById('editFormSmtpInput');
        const display = wrapper.querySelector('.selected-value');
        const optionsContainer = wrapper.querySelector('.custom-select-options');
        
        input.value = value || '';
        
        // Get text from clicked option
        const text = el.querySelector('span').innerText;
        const sub = el.querySelector('div div span:nth-child(2)')?.innerText;
        const label = sub ? `${text} (${sub})` : text;
        
        display.innerText = label;
        optionsContainer.style.display = 'none';
    }

    document.getElementById('editFormTemplateForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.is_active = this.is_active.checked ? 1 : 0;

        fetch(`{{ url('templates/forms') }}/${editingFormId}/update`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Operation failed');
            return data;
        })
        .then(data => {
            // Set session storage for toast on reload
            sessionStorage.setItem('flash_message', data.message);
            sessionStorage.setItem('flash_type', 'success');
            
            closeEditFormTemplateModal();
            location.reload();
        })
        .catch(err => showToast(err.message, 'error'));
    };
</script>
