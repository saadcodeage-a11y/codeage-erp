@extends('layouts.app')

@section('title', 'Employees')

@section('content')
@php
    $canCreateEmployees = Auth::user()->canAccessModule('employees', 'create');
    $canEditEmployees = Auth::user()->canAccessModule('employees', 'edit');
@endphp
<div class="page-header">
    <div class="header-left">
        <h1>Employee Management</h1>
        <p>Manage your team members and their information</p>
    </div>
    <div class="header-right">
        @if($canCreateEmployees)
            <button class="btn btn-outline" onclick="openInviteModal()">
                <i data-lucide="send"></i> Invite Employee
            </button>
            <button class="btn btn-outline" onclick="openEmployeeImportModal()">
                <i data-lucide="file-up"></i> Import Employees
            </button>
            <a href="#" onclick="openModal(); return false;" class="btn btn-primary" style="text-decoration: none;">
                <i data-lucide="plus"></i> Add Employee
            </a>
        @endif
    </div>
</div>

<!-- Tabs -->
<div class="tabs-container">
    <a href="{{ route('employees.index', ['status' => 'active']) }}" class="tab-item {{ $status == 'active' ? 'active' : '' }}">
        Active <span class="badge">{{ $counts['active'] }}</span>
    </a>
    <a href="{{ route('employees.index', ['status' => 'invited']) }}" class="tab-item {{ $status == 'invited' ? 'active' : '' }}">
        Invited <span class="badge">{{ $counts['invited'] }}</span>
    </a>
    <a href="{{ route('employees.index', ['status' => 'pending_approval']) }}" class="tab-item {{ $status == 'pending_approval' ? 'active' : '' }}">
        Pending Approval <span class="badge">{{ $counts['pending_approval'] }}</span>
    </a>
    <a href="{{ route('employees.index', ['status' => 'inactive']) }}" class="tab-item {{ $status == 'inactive' ? 'active' : '' }}">
        Inactive <span class="badge">{{ $counts['inactive'] }}</span>
    </a>
</div>

<!-- Search -->
<div class="search-container">
    <form method="GET" action="{{ route('employees.index') }}" class="search-form">
        <input type="hidden" name="status" value="{{ $status }}">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" name="search" placeholder="Search by name, ID, or position..." value="{{ request('search') }}" class="search-input">
    </form>
</div>

@if(session('success') || session('warning') || session('employeeImportSummary') || $errors->employeeImport->any())
    <div class="employee-feedback-stack">
        @if(session('success'))
            <div class="employee-status-banner success">
                <i data-lucide="circle-check-big"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('warning'))
            <div class="employee-status-banner warning">
                <i data-lucide="triangle-alert"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if(session('employeeImportSummary'))
            @php($importSummary = session('employeeImportSummary'))
            <div class="employee-import-summary">
                <div class="employee-import-summary-header">
                    <div>
                        <h3>Employee Upload Summary</h3>
                        <p>Imported records were saved under the default <strong>Unassigned</strong> department with position <strong>Not assigned</strong>.</p>
                    </div>
                    <div class="employee-import-metrics">
                        <span class="summary-chip success">{{ $importSummary['imported'] }} imported</span>
                        <span class="summary-chip muted">{{ count($importSummary['errors']) }} skipped</span>
                        <span class="summary-chip muted">Counter: {{ $importSummary['counter'] }}</span>
                    </div>
                </div>

                @if(!empty($importSummary['errors']))
                    <div class="employee-import-errors">
                        <strong>Import Issues</strong>
                        <ul>
                            @foreach(array_slice($importSummary['errors'], 0, 8) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @if(count($importSummary['errors']) > 8)
                            <p>{{ count($importSummary['errors']) - 8 }} more row issues were skipped during import.</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if($errors->employeeImport->any())
            <div class="employee-status-banner danger">
                <i data-lucide="octagon-alert"></i>
                <div>
                    <strong>Employee upload could not be completed.</strong>
                    <p>{{ $errors->employeeImport->first() }}</p>
                </div>
            </div>
        @endif
    </div>
@endif

<!-- Table -->
<div class="table-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Employee ID</th>
                <th>Position</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
            <tr>
                <td>
                    <div class="employee-cell">
                        <div class="avatar-sm {{ $employee->status == 'invited' ? 'orange' : 'red' }}" style="{{ !empty($employee->profile_picture) ? 'background-image: url(' . asset('storage/' . ltrim($employee->profile_picture, '/')) . ');' : '' }}">
                            @if(empty($employee->profile_picture))
                                {{ substr($employee->full_name, 0, 2) }}
                            @endif
                        </div>
                        <div class="employee-info">
                            <span class="emp-name">{{ $employee->full_name }}</span>
                            <span class="emp-email">{{ $employee->email }}</span>
                        </div>
                    </div>
                </td>
                <td>{{ $employee->employee_id ?: 'Not assigned' }}</td>
                <td>{{ $employee->designation ?: 'Not assigned' }}</td>
                <td>
                    <span class="status-badge {{ $employee->status }}">
                        {{ ucfirst(str_replace('_', ' ', $employee->status)) }}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        @if($employee->status == 'pending_approval')
                           @if($canEditEmployees)
                               <button type="button" onclick="approveEmployee({{ $employee->id }})" class="btn-action outline-green"><i data-lucide="check"></i> Approve</button>
                               <button type="button" onclick="disapproveEmployee({{ $employee->id }})" class="btn-action outline-red"><i data-lucide="x"></i> Disapprove</button>
                           @endif
                           <a href="{{ route('employees.show', $employee) }}" class="btn-action outline"><i data-lucide="eye"></i> Review</a>
                        @else
                           <a href="{{ route('employees.show', $employee) }}" class="btn-action outline"><i data-lucide="eye"></i> View</a>
                           @if($canEditEmployees && $employee->status != 'invited')
                               <button type="button" onclick="editEmployee({{ $employee->id }})" class="btn-action outline"><i data-lucide="edit-2"></i> Edit</button>
                           @endif
                        @endif

                        @if($canEditEmployees)
                            <div class="dropdown">
                                <button class="btn-action icon-only dropdown-toggle" onclick="toggleDropdown(this)">
                                    <i data-lucide="more-vertical"></i>
                                </button>
                                <div class="dropdown-menu">
                                    @if($employee->status == 'active')
                                        <button type="button" class="dropdown-item" onclick="openInactiveModal({{ $employee->id }})">
                                            <i data-lucide="user-minus" style="color: #6b7280;"></i> Mark as Inactive
                                        </button>
                                        <button type="button" class="dropdown-item" onclick="updateStatus({{ $employee->id }}, 'resigned')">
                                            <i data-lucide="log-out" style="color: #6b7280;"></i> Mark as Resigned
                                        </button>
                                        <button type="button" class="dropdown-item" onclick="updateStatus({{ $employee->id }}, 'terminated')">
                                            <i data-lucide="shield-x" style="color: #b91c1c;"></i> Mark as Terminated
                                        </button>
                                    @else
                                        <button type="button" class="dropdown-item" onclick="updateStatus({{ $employee->id }}, 'active')">
                                            <i data-lucide="user-check" style="color: #10B981;"></i> Mark as Active
                                        </button>
                                    @endif
                                    <hr style="border: 0; border-top: 1px solid #f3f4f6; margin: 4px 0;">
                                    <button type="button" class="dropdown-item text-red" onclick="deleteEmployee({{ $employee->id }})">
                                        <i data-lucide="trash-2"></i> Delete Employee
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">No employees found in this category.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="pagination-wrapper">
        {{ $employees->links() }}
    </div>
</div>
@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        openModal();
    });
</script>
@endif
@if($errors->employeeImport->any() || session('open_modal') === 'employeeImportModal')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        openEmployeeImportModal();
    });
</script>
@endif

<!-- Add Employee Modal -->
<div id="addEmployeeModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2>Add New Employee</h2>
                <p class="modal-desc" style="margin-bottom: 0;">Enter the employee's information to add them to the system. All fields are optional.</p>
            </div>
            <button onclick="closeModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            
            <!-- Global Error Summary -->
            @if ($errors->any())
                <div class="alert alert-danger" style="background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    <strong style="display: block; margin-bottom: 4px;">Please check the following errors:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- Profile Picture -->
                <div class="form-section profile-section" style="border-bottom: none; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 10px; border: none;">Employee Profile Picture</h3>
                    <div class="profile-upload">
                        <div id="profilePreview" class="profile-placeholder" style="width: 80px; height: 80px; font-size: 32px; overflow: hidden; background-position: center; background-size: cover;">
                            <i data-lucide="user"></i>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <div class="upload-btn-wrapper">
                                <button type="button" class="btn btn-outline">
                                    <i data-lucide="upload"></i> Upload Photo
                                </button>
                                <input type="file" name="profile_picture" accept="image/*" id="profilePhotoInput" onchange="previewProfilePhoto(this)">
                            </div>
                            <span class="hint">Recommended: Square image, max 2MB</span>
                            @error('profile_picture') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" required placeholder="Enter full name" value="{{ old('full_name') }}" class="@error('full_name') border-red-500 @enderror">
                            @error('full_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>CNIC</label>
                            <input type="text" name="cnic" placeholder="XXXXX-XXXXXXX-X" value="{{ old('cnic') }}" class="@error('cnic') border-red-500 @enderror">
                            @error('cnic') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required placeholder="email@example.com" value="{{ old('email') }}" class="@error('email') border-red-500 @enderror">
                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" placeholder="+92 xxx xxxxxxx" value="{{ old('phone') }}">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">Select gender</option>
                                <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" value="{{ old('dob') }}">
                        </div>
                        <div class="form-group full-width">
                            <label>Current Address</label>
                            <input type="text" name="current_address" placeholder="Enter current address" value="{{ old('current_address') }}">
                        </div>
                        <div class="form-group full-width">
                            <label>Permanent Address</label>
                            <input type="text" name="permanent_address" placeholder="Enter permanent address" value="{{ old('permanent_address') }}">
                        </div>
                         <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" placeholder="Enter father's name" value="{{ old('father_name') }}">
                        </div>
                         <div class="form-group">
                            <label>Father/Guardian Contact</label>
                            <input type="text" name="guardian_contact" placeholder="+92 xxx xxxxxxx" value="{{ old('guardian_contact') }}">
                        </div>
                        <div class="form-group">
                            <label>Education Level</label>
                            <select name="education_level">
                                <option value="">Select education level</option>
                                <option value="Bachelors" {{ old('education_level') == 'Bachelors' ? 'selected' : '' }}>Bachelors</option>
                                <option value="Masters" {{ old('education_level') == 'Masters' ? 'selected' : '' }}>Masters</option>
                                <option value="PhD" {{ old('education_level') == 'PhD' ? 'selected' : '' }}>PhD</option>
                                <option value="Intermediate" {{ old('education_level') == 'Intermediate' ? 'selected' : '' }}>Intermediate</option>
                            </select>
                        </div>
                         <div class="form-group">
                            <label>Field of Study / Major</label>
                            <input type="text" name="field_of_study" placeholder="e.g., Computer Science, Business" value="{{ old('field_of_study') }}">
                        </div>
                    </div>
                </div>

                <!-- Job Information -->
                <div class="form-section">
                    <h3>Job Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hiring Date</label>
                            <input type="date" name="hiring_date" value="{{ old('hiring_date') }}">
                        </div>
                        <div class="form-group">
                            <label>Hiring Position</label>
                            <input type="text" name="designation" required placeholder="e.g. Software Developer" value="{{ old('designation') }}" class="@error('designation') border-red-500 @enderror">
                            @error('designation') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Job Location</label>
                            <select name="job_location">
                                <option value="">Select location</option>
                                <option value="On-site" {{ old('job_location') == 'On-site' ? 'selected' : '' }}>On-site</option>
                                <option value="Remote" {{ old('job_location') == 'Remote' ? 'selected' : '' }}>Remote</option>
                                <option value="Hybrid" {{ old('job_location') == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Shift Start Time</label>
                            <input type="time" name="shift_start_time" value="{{ old('shift_start_time') }}">
                            @error('shift_start_time') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                             <label>Department *</label>
                            <select name="department_id" required class="@error('department_id') border-red-500 @enderror">
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Team Manager</label>
                            <select name="team_manager_user_id">
                                <option value="">Select Team Manager</option>
                                @foreach($teamManagers as $manager)
                                    <option value="{{ $manager->id }}" {{ old('team_manager_user_id') == $manager->id ? 'selected' : '' }}>
                                        {{ $manager->name }}{{ $manager->employee_id ? ' (' . $manager->employee_id . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('team_manager_user_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Shift End Time</label>
                            <input type="time" name="shift_end_time" value="{{ old('shift_end_time') }}">
                            @error('shift_end_time') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                         <div class="form-group full-width">
                            <label>Payroll Status</label>
                            <select name="payroll_status">
                                <option value="">Select payroll status</option>
                                <option value="Paid" {{ old('payroll_status') == 'Paid' ? 'selected' : '' }}>Paid</option>
                                <option value="Unpaid" {{ old('payroll_status') == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="Internship" {{ old('payroll_status') == 'Internship' ? 'selected' : '' }}>Internship</option>
                            </select>
                        </div>
                         <div class="form-group full-width">
                            <label>Employee Status</label>
                            <select name="status" id="employee_status" onchange="toggleInactiveReasonField()">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="invited" {{ old('status') == 'invited' ? 'selected' : '' }}>Invited</option>
                                <option value="pending_approval" {{ old('status') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                <option value="resigned" {{ old('status') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                                <option value="terminated" {{ old('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                            </select>
                        </div>
                        <div class="form-group full-width" id="inactiveReasonGroup" style="display: none;">
                            <label>Reason for Inactivation *</label>
                            <textarea name="inactive_reason" id="inactive_reason" rows="3" placeholder="Explain why this employee is being marked inactive" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;">{{ old('inactive_reason') }}</textarea>
                            @error('inactive_reason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>HR Manager Comments</h3>
                     <div class="form-group">
                        <textarea name="hr_comments" rows="3" placeholder="Any additional notes or comments..." style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;">{{ old('hr_comments') }}</textarea>
                    </div>
                </div>

                 <!-- Document Uploads -->
                <div class="form-section">
                    <h3>Document Uploads</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>CNIC Front</label>
                            <input type="file" name="cnic_front" class="file-input">
                        </div>
                         <div class="form-group">
                            <label>CNIC Back</label>
                            <input type="file" name="cnic_back" class="file-input">
                        </div>
                        <div class="form-group full-width">
                            <label>CV/Resume Upload</label>
                            <input type="file" name="cv" class="file-input">
                        </div>
                        <div class="form-group full-width">
                            <label>Educational Documents / Transcript</label>
                            <input type="file" name="transcript" class="file-input">
                        </div>
                    </div>
                </div>

                <!-- Banking Information -->
                <div class="form-section">
                    <h3>Banking Information</h3>
                    <div class="form-group">
                        <label>Do they have a Bank Account?</label>
                        <select id="bankToggle" onchange="toggleBankFields()">
                            <option value="No" {{ old('bank_id') || old('bank_account_title') || old('bank_account_number') || old('iban') ? '' : 'selected' }}>No</option>
                            <option value="Yes" {{ old('bank_id') || old('bank_account_title') || old('bank_account_number') || old('iban') ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                    <div id="bankFields" class="form-grid" style="display: none; margin-top: 15px;">
                        <div class="form-group">
                            <label>Bank</label>
                            <select name="bank_id" id="bank_id">
                                <option value="">Select Bank</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}" {{ (string) old('bank_id') === (string) $bank->id ? 'selected' : '' }}>
                                        {{ $bank->name }}{{ $bank->code ? ' (' . $bank->code . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Account Title</label>
                            <input type="text" name="bank_account_title" id="bank_account_title" placeholder="Account Title" value="{{ old('bank_account_title') }}">
                        </div>
                         <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" name="bank_account_number" id="bank_account_number" placeholder="Account Number" value="{{ old('bank_account_number') }}">
                        </div>
                         <div class="form-group">
                            <label>IBAN</label>
                            <input type="text" name="iban" id="bank_iban" placeholder="IBAN" value="{{ old('iban') }}">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label>HR Manager Comments (Optional)</label>
                        <textarea name="banking_comments" rows="3" placeholder="Any additional notes or comments..." style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;">{{ old('banking_comments') }}</textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #FF4A00; color: white;">+ Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="employeeImportModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 560px;">
        <div class="modal-header">
            <div>
                <h2>Import Employees from CSV</h2>
                <p class="modal-desc" style="margin-bottom: 0;">Upload a CSV with Employee ID, Employee Name, Email, Contact Number, CNIC, and Gender. Imported employees will be created as active records.</p>
            </div>
            <button onclick="closeEmployeeImportModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            @if($errors->employeeImport->any())
                <div class="employee-inline-error">
                    <strong>Upload Error</strong>
                    <ul>
                        @foreach($errors->employeeImport->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf
                <div class="employee-import-spec">
                    <h4>Expected CSV Columns</h4>
                    <p><code>Employee ID</code>, <code>Employee Name</code>, <code>Email</code>, <code>Contact Number</code>, <code>CNIC</code>, <code>Gender</code></p>
                    <p>The employee counter will be updated to the highest imported number for the current employee ID prefix.</p>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label>Employee CSV File</label>
                    <input type="file" name="employee_csv" accept=".csv" required>
                    <span class="hint">Rows with duplicate employee IDs, duplicate emails, or invalid genders will be skipped.</span>
                </div>
                <div class="modal-footer" style="padding: 0; margin-top: 8px;">
                    <button type="button" onclick="closeEmployeeImportModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="upload"></i> Upload CSV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Invite Employee Modal -->
<div id="inviteEmployeeModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <div>
                <h2>Invite Employee</h2>
                <p class="modal-desc">Send an invitation email to a new team member.</p>
            </div>
            <button onclick="closeInviteModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <form id="inviteEmployeeForm">
                @csrf
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="Enter full name" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Email Address *</label>
                    <input type="email" name="email" required placeholder="email@example.com" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department_id" required style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Position *</label>
                        <input type="text" name="designation" required placeholder="e.g. Developer" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                    </div>
                </div>
                <div class="modal-footer" style="margin-top: 20px; padding: 0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeInviteModal()" class="btn btn-outline" style="padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; cursor: pointer;">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="sendInviteBtn" style="padding: 10px 20px; background: #FF4A00; color: white; border: none; border-radius: 6px; cursor: pointer;">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Employee Modal -->
<div id="approveEmployeeModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <div>
                <h2>Approve Application</h2>
                <p class="modal-desc">Set the start details for this employee.</p>
            </div>
            <button onclick="closeApproveModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <form id="approveEmployeeForm">
                @csrf
                <input type="hidden" id="approve_employee_id">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Start Date *</label>
                    <input type="date" id="approve_start_date" required style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Start Time *</label>
                    <input type="time" id="approve_start_time" required value="09:00" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px;">
                </div>
                <div class="modal-footer" style="margin-top: 20px; padding: 0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeApproveModal()" class="btn btn-outline" style="padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; cursor: pointer;">Cancel</button>
                    <button type="button" onclick="submitApproval()" class="btn btn-primary" id="confirmApproveBtn" style="padding: 10px 20px; background: #22c55e; color: white; border: none; border-radius: 6px; cursor: pointer;">Approve & Send Welcome Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="inactiveEmployeeModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <div>
                <h2>Mark Employee as Inactive</h2>
                <p class="modal-desc">A reason is required before this employee can be marked inactive.</p>
            </div>
            <button onclick="closeInactiveModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <form id="inactiveEmployeeForm">
                @csrf
                <input type="hidden" id="inactive_employee_id">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Reason for Inactivation *</label>
                    <textarea id="inactive_employee_reason" rows="4" required placeholder="Explain why this employee is being marked inactive" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;"></textarea>
                    <span id="inactiveEmployeeError" class="text-red-500 text-xs" style="display: none; margin-top: 6px;"></span>
                </div>
                <div class="modal-footer" style="margin-top: 20px; padding: 0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeInactiveModal()" class="btn btn-outline" style="padding: 10px 20px; border: 1px solid #e5e7eb; border-radius: 6px; background: white; cursor: pointer;">Cancel</button>
                    <button type="button" onclick="submitInactiveStatus()" class="btn btn-primary" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer;">Save Reason & Mark Inactive</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div id="editEmployeeModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2>Edit Employee</h2>
                <p class="modal-desc" style="margin-bottom: 0;">Update the employee's information. Fields marked with * are required.</p>
            </div>
            <button onclick="closeEditModal()" class="close-btn"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <div id="editErrorSummary" class="alert alert-danger" style="display: none; background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <strong style="display: block; margin-bottom: 4px;">Please check the following errors:</strong>
                <ul id="editErrorList" style="margin: 0; padding-left: 20px;"></ul>
            </div>

            <form id="editEmployeeForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <!-- Profile Picture -->
                <div class="form-section profile-section" style="border-bottom: none; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 10px; border: none;">Employee Profile Picture</h3>
                    <div class="profile-upload">
                        <div id="edit_profilePreview" class="profile-placeholder" style="width: 80px; height: 80px; font-size: 32px; overflow: hidden; background-position: center; background-size: cover;">
                            <i data-lucide="user"></i>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <div class="upload-btn-wrapper">
                                <button type="button" class="btn btn-outline">
                                    <i data-lucide="upload"></i> Change Photo
                                </button>
                                <input type="file" name="profile_picture" accept="image/*" id="edit_profilePhotoInput" onchange="previewEditProfilePhoto(this)">
                            </div>
                            <span class="hint">Recommended: Square image, max 2MB</span>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Personal Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" id="edit_full_name" required>
                        </div>
                        <div class="form-group">
                            <label>CNIC</label>
                            <input type="text" name="cnic" id="edit_cnic">
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" id="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" id="edit_phone">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" id="edit_gender">
                                <option value="">Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" id="edit_dob">
                        </div>
                        <div class="form-group full-width">
                            <label>Current Address</label>
                            <input type="text" name="current_address" id="edit_current_address">
                        </div>
                        <div class="form-group full-width">
                            <label>Permanent Address</label>
                            <input type="text" name="permanent_address" id="edit_permanent_address">
                        </div>
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_name" id="edit_father_name">
                        </div>
                        <div class="form-group">
                            <label>Father/Guardian Contact</label>
                            <input type="text" name="guardian_contact" id="edit_guardian_contact">
                        </div>
                        <div class="form-group">
                            <label>Education Level</label>
                            <select name="education_level" id="edit_education_level">
                                <option value="">Select education level</option>
                                <option value="Bachelors">Bachelors</option>
                                <option value="Masters">Masters</option>
                                <option value="PhD">PhD</option>
                                <option value="Intermediate">Intermediate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Field of Study / Major</label>
                            <input type="text" name="field_of_study" id="edit_field_of_study">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Job Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Hiring Date</label>
                            <input type="date" name="hiring_date" id="edit_hiring_date">
                        </div>
                        <div class="form-group">
                            <label>Hiring Position</label>
                            <input type="text" name="designation" id="edit_designation" required>
                        </div>
                        <div class="form-group">
                            <label>Job Location</label>
                            <select name="job_location" id="edit_job_location">
                                <option value="">Select location</option>
                                <option value="On-site">On-site</option>
                                <option value="Remote">Remote</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Shift Start Time</label>
                            <input type="time" name="shift_start_time" id="edit_shift_start_time">
                        </div>
                        <div class="form-group">
                            <label>Department *</label>
                            <select name="department_id" id="edit_department_id" required>
                                <option value="">Select Department</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Team Manager</label>
                            <select name="team_manager_user_id" id="edit_team_manager_user_id">
                                <option value="">Select Team Manager</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Shift End Time</label>
                            <input type="time" name="shift_end_time" id="edit_shift_end_time">
                        </div>
                        <div class="form-group full-width">
                            <label>Payroll Status</label>
                            <select name="payroll_status" id="edit_payroll_status">
                                <option value="">Select payroll status</option>
                                <option value="Paid">Paid</option>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Internship">Internship</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label>Employee Status</label>
                            <select name="status" id="edit_status" onchange="toggleEditInactiveReasonField()">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="invited">Invited</option>
                                <option value="pending_approval">Pending Approval</option>
                                <option value="resigned">Resigned</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                        <div class="form-group full-width" id="editInactiveReasonGroup" style="display: none;">
                            <label>Reason for Inactivation *</label>
                            <textarea name="inactive_reason" id="edit_inactive_reason" rows="3" placeholder="Explain why this employee is being marked inactive" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>HR Manager Comments</h3>
                    <div class="form-group">
                        <textarea name="hr_comments" id="edit_hr_comments" rows="3" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;"></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Document Uploads</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>CNIC Front</label>
                            <input type="file" name="cnic_front" class="file-input">
                            <div id="edit_cnic_front_status"></div>
                        </div>
                        <div class="form-group">
                            <label>CNIC Back</label>
                            <input type="file" name="cnic_back" class="file-input">
                            <div id="edit_cnic_back_status"></div>
                        </div>
                        <div class="form-group full-width">
                            <label>CV/Resume Upload</label>
                            <input type="file" name="cv" class="file-input">
                            <div id="edit_cv_status"></div>
                        </div>
                        <div class="form-group full-width">
                            <label>Educational Documents / Transcript</label>
                            <input type="file" name="transcript" class="file-input">
                            <div id="edit_transcript_status"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Banking Information</h3>
                    <div class="form-group">
                        <label>Do they have a Bank Account?</label>
                        <select id="edit_bankToggle" onchange="toggleEditBankFields()">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div id="edit_bankFields" class="form-grid" style="display: none; margin-top: 15px;">
                        <div class="form-group">
                            <label>Bank</label>
                            <select name="bank_id" id="edit_bank_id">
                                <option value="">Select Bank</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Account Title</label>
                            <input type="text" name="bank_account_title" id="edit_bank_account_title">
                        </div>
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" name="bank_account_number" id="edit_bank_account_number">
                        </div>
                        <div class="form-group">
                            <label>IBAN</label>
                            <input type="text" name="iban" id="edit_iban">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>Banking Comments</label>
                        <textarea name="banking_comments" id="edit_banking_comments" rows="3" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-family: inherit;"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #FF4A00; color: white;">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('addEmployeeModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('addEmployeeModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function openEmployeeImportModal() {
        document.getElementById('employeeImportModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeEmployeeImportModal() {
        document.getElementById('employeeImportModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function editEmployee(id) {
        fetch(`/employees/${id}/edit`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            const employee = data.employee;
            const departments = data.departments;
            const banks = data.banks;
            const teamManagers = data.teamManagers;
            
            // Set Form Action
            document.getElementById('editEmployeeForm').action = `/employees/${id}`;
            
            // Populate Departments
            const deptSelect = document.getElementById('edit_department_id');
            deptSelect.innerHTML = '<option value="">Select Department</option>';
            departments.forEach(dept => {
                const opt = document.createElement('option');
                opt.value = dept.id;
                opt.textContent = dept.name;
                if (employee.department_id == dept.id) opt.selected = true;
                deptSelect.appendChild(opt);
            });

            const bankSelect = document.getElementById('edit_bank_id');
            bankSelect.innerHTML = '<option value="">Select Bank</option>';
            banks.forEach(bank => {
                const opt = document.createElement('option');
                opt.value = bank.id;
                opt.textContent = bank.code ? `${bank.name} (${bank.code})` : bank.name;
                if (employee.bank_id == bank.id) opt.selected = true;
                bankSelect.appendChild(opt);
            });

            const managerSelect = document.getElementById('edit_team_manager_user_id');
            managerSelect.innerHTML = '<option value="">Select Team Manager</option>';
            teamManagers.forEach(manager => {
                const opt = document.createElement('option');
                opt.value = manager.id;
                opt.textContent = manager.employee_id ? `${manager.name} (${manager.employee_id})` : manager.name;
                if (String(employee.team_manager_user_id || '') === String(manager.id)) opt.selected = true;
                managerSelect.appendChild(opt);
            });

            // Populate Fields
            document.getElementById('edit_full_name').value = employee.full_name || '';
            document.getElementById('edit_cnic').value = employee.cnic || '';
            document.getElementById('edit_email').value = employee.email || '';
            document.getElementById('edit_phone').value = employee.phone || '';
            document.getElementById('edit_gender').value = employee.gender || '';
            document.getElementById('edit_dob').value = employee.dob ? employee.dob.split('T')[0] : '';
            document.getElementById('edit_current_address').value = employee.current_address || '';
            document.getElementById('edit_permanent_address').value = employee.permanent_address || '';
            document.getElementById('edit_father_name').value = employee.father_name || '';
            document.getElementById('edit_guardian_contact').value = employee.guardian_contact || '';
            document.getElementById('edit_education_level').value = employee.education_level || '';
            document.getElementById('edit_field_of_study').value = employee.field_of_study || '';
            document.getElementById('edit_hiring_date').value = employee.hiring_date ? employee.hiring_date.split('T')[0] : '';
            document.getElementById('edit_designation').value = employee.designation || '';
            document.getElementById('edit_job_location').value = employee.job_location || '';
            document.getElementById('edit_shift_start_time').value = employee.shift_start_time ? employee.shift_start_time.substring(0, 5) : '';
            document.getElementById('edit_shift_end_time').value = employee.shift_end_time ? employee.shift_end_time.substring(0, 5) : '';
            document.getElementById('edit_payroll_status').value = employee.payroll_status || '';
            document.getElementById('edit_status').value = employee.status || '';
            document.getElementById('edit_inactive_reason').value = employee.inactive_reason || '';
            document.getElementById('edit_hr_comments').value = employee.hr_comments || '';
            
            // Bank Toggle
            if (employee.bank_id || employee.bank_name) {
                document.getElementById('edit_bankToggle').value = 'Yes';
                document.getElementById('edit_bankFields').style.display = 'grid';
            } else {
                document.getElementById('edit_bankToggle').value = 'No';
                document.getElementById('edit_bankFields').style.display = 'none';
            }
            document.getElementById('edit_bank_account_title').value = employee.bank_account_title || '';
            document.getElementById('edit_bank_account_number').value = employee.bank_account_number || '';
            document.getElementById('edit_iban').value = employee.iban || '';
            document.getElementById('edit_banking_comments').value = employee.banking_comments || '';

            // Profile Preview
            const preview = document.getElementById('edit_profilePreview');
            if (employee.profile_picture) {
                preview.innerHTML = '';
                const storagePath = "{{ asset('storage') }}";
                preview.style.backgroundImage = `url('${storagePath}/${employee.profile_picture.replace(/^\//, '')}')`;
                preview.style.border = '1px solid #e5e7eb';
            } else {
                preview.style.backgroundImage = 'none';
                preview.innerHTML = '<i data-lucide="user"></i>';
                preview.style.border = 'none';
                if (window.lucide) window.lucide.createIcons();
            }

            // Document Statuses
            document.getElementById('edit_cnic_front_status').innerHTML = employee.cnic_front_path ? '<small style="color: green">Uploaded</small>' : '';
            document.getElementById('edit_cnic_back_status').innerHTML = employee.cnic_back_path ? '<small style="color: green">Uploaded</small>' : '';
            document.getElementById('edit_cv_status').innerHTML = employee.cv_path ? '<small style="color: green">Uploaded</small>' : '';
            document.getElementById('edit_transcript_status').innerHTML = employee.transcript_path ? '<small style="color: green">Uploaded</small>' : '';

            toggleEditInactiveReasonField();
            openEditModal();
        });
    }

    function openEditModal() {
        document.getElementById('editEmployeeModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeEditModal() {
        document.getElementById('editEmployeeModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function toggleEditBankFields() {
        var val = document.getElementById('edit_bankToggle').value;
        var fields = document.getElementById('edit_bankFields');
        var shouldShow = val === 'Yes';
        fields.style.display = shouldShow ? 'grid' : 'none';

        if (!shouldShow) {
            document.getElementById('edit_bank_id').value = '';
            document.getElementById('edit_bank_account_title').value = '';
            document.getElementById('edit_bank_account_number').value = '';
            document.getElementById('edit_iban').value = '';
        }
    }

    function toggleInactiveReasonField() {
        const status = document.getElementById('employee_status').value;
        const group = document.getElementById('inactiveReasonGroup');
        const field = document.getElementById('inactive_reason');
        const isInactive = status === 'inactive';

        group.style.display = isInactive ? 'block' : 'none';
        field.required = isInactive;

        if (!isInactive) {
            field.value = '';
        }
    }

    function toggleEditInactiveReasonField() {
        const status = document.getElementById('edit_status').value;
        const group = document.getElementById('editInactiveReasonGroup');
        const field = document.getElementById('edit_inactive_reason');
        const isInactive = status === 'inactive';

        group.style.display = isInactive ? 'block' : 'none';
        field.required = isInactive;

        if (!isInactive) {
            field.value = '';
        }
    }

    function previewEditProfilePhoto(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('edit_profilePreview');
                preview.innerHTML = '';
                preview.style.backgroundImage = 'url(' + e.target.result + ')';
                preview.style.backgroundColor = 'transparent';
                preview.style.border = '1px solid #e5e7eb';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function toggleBankFields() {
        var val = document.getElementById('bankToggle').value;
        var fields = document.getElementById('bankFields');
        var shouldShow = val === 'Yes';
        fields.style.display = shouldShow ? 'grid' : 'none';

        if (!shouldShow) {
            document.getElementById('bank_id').value = '';
            document.getElementById('bank_account_title').value = '';
            document.getElementById('bank_account_number').value = '';
            document.getElementById('bank_iban').value = '';
        }
    }

    function previewProfilePhoto(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('profilePreview');
                preview.innerHTML = '';
                preview.style.backgroundImage = 'url(' + e.target.result + ')';
                preview.style.backgroundColor = 'transparent';
                preview.style.border = '1px solid #e5e7eb';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function openInactiveModal(id) {
        document.getElementById('inactive_employee_id').value = id;
        document.getElementById('inactive_employee_reason').value = '';
        document.getElementById('inactiveEmployeeError').textContent = '';
        document.getElementById('inactiveEmployeeError').style.display = 'none';
        document.getElementById('inactiveEmployeeModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        if (window.lucide) window.lucide.createIcons();
    }

    function closeInactiveModal() {
        document.getElementById('inactiveEmployeeModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function submitInactiveStatus() {
        const id = document.getElementById('inactive_employee_id').value;
        const reason = document.getElementById('inactive_employee_reason').value.trim();
        const error = document.getElementById('inactiveEmployeeError');

        if (!reason) {
            error.textContent = 'Reason is required.';
            error.style.display = 'block';
            return;
        }

        updateStatus(id, 'inactive', reason);
    }

    function updateStatus(id, status, inactiveReason = null) {
        if (status !== 'inactive' && !confirm(`Are you sure you want to mark this employee as ${status}?`)) return;

        fetch(`/employees/${id}/status`, {
            method: 'PATCH',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                status: status,
                inactive_reason: inactiveReason
            })
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                if (response.status === 422 && data.errors?.inactive_reason?.[0]) {
                    const error = document.getElementById('inactiveEmployeeError');

                    if (error) {
                        error.textContent = data.errors.inactive_reason[0];
                        error.style.display = 'block';
                    }
                } else {
                    alert(data.message || 'Failed to update status.');
                }

                return null;
            }

            return data;
        })
        .then(data => {
            if (data?.success) {
                closeInactiveModal();
                location.reload();
            }
        });
    }

    function deleteEmployee(id) {
        if (!confirm('Are you sure you want to delete this employee? This action cannot be undone.')) return;

        fetch(`/employees/${id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to delete employee.');
            }
        });
    }

    function toggleDropdown(btn) {
        event.stopPropagation(); // Prevent immediate closing by the listener above
        const menu = btn.nextElementSibling;
        // Close all other dropdowns
        document.querySelectorAll('.dropdown-menu').forEach(m => {
            if (m !== menu) {
                m.classList.remove('show');
            }
        });
        menu.classList.toggle('show');
    }

    // Close on click outside
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('addEmployeeModal')) {
            closeModal();
        }
        if (event.target == document.getElementById('employeeImportModal')) {
            closeEmployeeImportModal();
        }
        if (event.target == document.getElementById('editEmployeeModal')) {
            closeEditModal();
        }
        if (event.target == document.getElementById('inviteEmployeeModal')) {
            closeInviteModal();
        }
        if (event.target == document.getElementById('approveEmployeeModal')) {
            closeApproveModal();
        }
        if (event.target == document.getElementById('inactiveEmployeeModal')) {
            closeInactiveModal();
        }
        // Close dropdowns if clicking outside
        if (!event.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    toggleInactiveReasonField();
    toggleBankFields();
    function openInviteModal() {
        document.getElementById('inviteEmployeeModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeInviteModal() {
        document.getElementById('inviteEmployeeModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    document.getElementById('inviteEmployeeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('sendInviteBtn');
        btn.disabled = true;
        btn.textContent = 'Sending...';

        fetch('{{ route("employees.invite") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(Object.fromEntries(new FormData(this)))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Invitation sent successfully!');
                location.reload();
            } else {
                alert(data.message || 'Failed to send invitation.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please check console.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Send Invitation';
        });
    });

    function approveEmployee(id) {
        document.getElementById('approve_employee_id').value = id;
        document.getElementById('approveEmployeeModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeApproveModal() {
        document.getElementById('approveEmployeeModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function submitApproval() {
        const id = document.getElementById('approve_employee_id').value;
        const startDate = document.getElementById('approve_start_date').value;
        const startTime = document.getElementById('approve_start_time').value;

        if (!startDate || !startTime) {
            alert('Please fill in both start date and time.');
            return;
        }

        const btn = document.getElementById('confirmApproveBtn');
        btn.disabled = true;
        btn.textContent = 'Processing...';

        fetch(`/employees/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                start_date: startDate,
                start_time: startTime
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Employee approved and welcome email sent!');
                location.reload();
            } else {
                alert(data.message || 'Failed to approve employee.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please check console.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Approve & Send Welcome Email';
        });
    }

    function disapproveEmployee(id) {
        if (!confirm('Are you sure you want to disapprove this application?')) return;
        fetch(`/employees/${id}/disapprove`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => location.reload());
    }
</script>

<style>
    .employee-feedback-stack {
        display: grid;
        gap: 14px;
        margin-bottom: 24px;
    }
    .employee-status-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px 18px;
        border-radius: 12px;
        border: 1px solid;
    }
    .employee-status-banner i {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .employee-status-banner strong,
    .employee-status-banner span,
    .employee-status-banner p {
        color: inherit;
    }
    .employee-status-banner p {
        margin: 4px 0 0;
        font-size: 13px;
        line-height: 1.5;
    }
    .employee-status-banner.success {
        background: #f0fdf4;
        border-color: #bbf7d0;
        color: #166534;
    }
    .employee-status-banner.warning {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #9a3412;
    }
    .employee-status-banner.danger {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }
    .employee-import-summary {
        background: linear-gradient(180deg, #fffaf5 0%, #ffffff 100%);
        border: 1px solid #fed7aa;
        border-radius: 16px;
        padding: 20px;
        display: grid;
        gap: 16px;
    }
    .employee-import-summary-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
    }
    .employee-import-summary-header h3 {
        margin: 0 0 6px;
        font-size: 17px;
        color: #111827;
    }
    .employee-import-summary-header p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.6;
    }
    .employee-import-metrics {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .summary-chip {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #475569;
    }
    .summary-chip.success {
        background: #f0fdf4;
        border-color: #bbf7d0;
        color: #166534;
    }
    .summary-chip.muted {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #475569;
    }
    .employee-import-errors {
        border-top: 1px solid #ffedd5;
        padding-top: 16px;
    }
    .employee-import-errors strong {
        display: block;
        margin-bottom: 8px;
        color: #9a3412;
    }
    .employee-import-errors ul {
        margin: 0;
        padding-left: 18px;
        color: #7c2d12;
        display: grid;
        gap: 6px;
    }
    .employee-import-errors p {
        margin: 10px 0 0;
        color: #9a3412;
        font-size: 13px;
    }
    .employee-inline-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 14px 16px;
        border-radius: 12px;
    }
    .employee-inline-error strong {
        display: block;
        margin-bottom: 8px;
    }
    .employee-inline-error ul {
        margin: 0;
        padding-left: 18px;
    }
    .employee-import-spec {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
    }
    .employee-import-spec h4 {
        margin: 0 0 6px;
        font-size: 14px;
        color: #111827;
    }
    .employee-import-spec p {
        margin: 0;
        color: #475569;
        font-size: 13px;
        line-height: 1.6;
    }
    .employee-import-spec p + p {
        margin-top: 6px;
    }
    .border-red-500 {
        border-color: #ef4444 !important;
    }
    .text-red-500 {
        color: #ef4444;
    }
    .text-xs {
        font-size: 12px;
        margin-top: 4px;
    }
    @media (max-width: 900px) {
        .employee-import-summary-header {
            flex-direction: column;
        }
        .employee-import-metrics {
            justify-content: flex-start;
        }
    }
</style>
@endsection
