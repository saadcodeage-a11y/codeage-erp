@extends('layouts.app')

@section('title', $employee->first_name . ' ' . $employee->last_name . ' - Employee Details')

@section('content')
<div class="page-header" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <a href="{{ route('employees.index') }}" class="btn btn-outline" style="padding: 8px 12px; text-decoration: none;">
            <i data-lucide="arrow-left"></i> Back to List
        </a>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" onclick="editEmployee({{ $employee->id }})" class="btn btn-primary" style="background-color: #FF4A00; text-decoration: none;">
            <i data-lucide="edit"></i> Edit Details
        </button>
        <button type="button" onclick="deleteEmployee({{ $employee->id }})" class="btn btn-primary" style="background-color: #dc2626; text-decoration: none;">
            <i data-lucide="trash-2"></i> Delete Employee
        </button>
        @if($employee->status == 'pending_approval')
            <button type="button" onclick="approveEmployee({{ $employee->id }})" class="btn btn-primary" style="background-color: #10B981; text-decoration: none;">
                <i data-lucide="check"></i> Approve Application
            </button>
            <button type="button" onclick="disapproveEmployee({{ $employee->id }})" class="btn btn-outline" style="color: #dc2626; border-color: #dc2626; text-decoration: none;">
                <i data-lucide="x"></i> Disapprove
            </button>
        @else
            @if($employee->status == 'active')
                <button type="button" onclick="updateStatus({{ $employee->id }}, 'inactive')" class="btn btn-outline" style="text-decoration: none;">
                    <i data-lucide="user-minus"></i> Mark as Inactive
                </button>
            @else
                <button type="button" onclick="updateStatus({{ $employee->id }}, 'active')" class="btn btn-outline" style="text-decoration: none;">
                    <i data-lucide="user-check"></i> Mark as Active
                </button>
            @endif
        @endif
    </div>
</div>

<!-- Employee Header Card -->
<div class="card" style="margin-bottom: 24px; display: flex; align-items: center; gap: 24px;">
    <div class="avatar-lg" style="width: 80px; height: 80px; border-radius: 50%; background-color: #FF4A00; color: white; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 600; flex-shrink: 0; background-image: url('{{ $employee->profile_picture ? asset('storage/'.ltrim($employee->profile_picture, '/')) : '' }}'); background-size: cover; background-position: center;">
        @if(!$employee->profile_picture)
            {{ substr($employee->full_name, 0, 2) }}
        @endif
    </div>
    <div style="flex: 1;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 4px;">
            <h1 style="font-size: 24px; font-weight: 600; color: #111827; margin: 0;">{{ $employee->full_name }}</h1>
            <span class="status-badge {{ $employee->status }}" style="padding: 2px 10px;">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</span>
        </div>
        <p style="color: #6b7280; font-size: 16px; margin: 0 0 8px 0;">{{ $employee->designation }}</p>
        <div style="display: flex; align-items: center; gap: 8px; color: #9ca3af; font-size: 14px;">
            <i data-lucide="briefcase" style="width: 14px; height: 14px;"></i> {{ $employee->employee_id ?? 'N/A' }}
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="tabs-container" style="width: 100%; margin-bottom: 24px; padding: 0; background: transparent; border: none; border-bottom: 1px solid #e5e7eb; border-radius: 0;">
    <button class="tab-btn active" onclick="switchTab('personal')">Personal Information</button>
    <button class="tab-btn" onclick="switchTab('employment')">Employment Summary</button>
    <button class="tab-btn" onclick="switchTab('job')">Job Details</button>
    <button class="tab-btn" onclick="switchTab('documents')">Documents</button>
    <button class="tab-btn" onclick="switchTab('activity')">Activity Logs</button>
</div>

<!-- Tab Contents -->
<div class="card" style="min-height: 400px;">
    
    <!-- Personal Information Tab -->
    <div id="personal" class="tab-content active">
        <h3 class="section-title">Contact Information</h3>
        <div class="info-grid two-col">
            <div class="info-item">
                <label>Email</label>
                <div class="value-with-icon">
                    <i data-lucide="mail"></i> {{ $employee->email }}
                </div>
            </div>
            <div class="info-item">
                <label>Phone</label>
                <div class="value-with-icon">
                    <i data-lucide="phone"></i> {{ $employee->phone ?? 'Not provided' }}
                </div>
            </div>
        </div>

        <h3 class="section-title" style="margin-top: 32px;">Personal Details</h3>
        <div class="info-grid two-col">
            <div class="info-item">
                <label>CNIC</label>
                <p>{{ $employee->cnic ?? 'Not provided' }}</p>
            </div>
            <div class="info-item">
                <label>Gender</label>
                <p>{{ $employee->gender ?? 'Not provided' }}</p>
            </div>
            <div class="info-item">
                <label>Date of Birth</label>
                <p>{{ $employee->dob ? $employee->dob->format('d/m/Y') : 'Not provided' }}</p>
            </div>
            <div class="info-item">
                <label>Father's Name</label>
                <p>{{ $employee->father_name ?? 'Not provided' }}</p>
            </div>
            <div class="info-item">
                <label>Guardian Contact</label>
                <p>{{ $employee->guardian_contact ?? 'Not provided' }}</p>
            </div>
        </div>

        <div class="info-grid heading-only" style="margin-top: 20px;">
             <div class="info-item full-width">
                <label>Current Address</label>
                <p>{{ $employee->current_address ?? 'Not provided' }}</p>
            </div>
             <div class="info-item full-width">
                <label>Permanent Address</label>
                <p>{{ $employee->permanent_address ?? 'Not provided' }}</p>
            </div>
        </div>
    </div>

    <!-- Employment Summary Tab -->
    <div id="employment" class="tab-content" style="display: none;">
        <h3 class="section-title">Employment Overview</h3>
        <div class="info-grid two-col">
            <div class="info-item">
                <label>Position</label>
                <p>{{ $employee->designation }}</p>
            </div>
             <div class="info-item">
                <label>Payroll Status</label>
                <p>{{ $employee->payroll_status ?? 'Not specified' }}</p>
            </div>
             <div class="info-item">
                <label>Location</label>
                <p>{{ $employee->job_location ?? 'Not specified' }}</p>
            </div>
        </div>
    </div>

    <!-- Job Details Tab (Placeholder mostly same as employment for now, user screenshot shows 'Position & Dates') -->
    <div id="job" class="tab-content" style="display: none;">
        <h3 class="section-title">Position & Dates</h3>
        <div class="info-grid two-col">
             <div class="info-item">
                <label>Department</label>
                <p>{{ $employee->department->name }}</p>
            </div>
            <div class="info-item">
                <label>Hiring Date</label>
                <p>{{ $employee->hiring_date ? $employee->hiring_date->format('d F, Y') : 'Not specified' }}</p>
            </div>
        </div>
    </div>

    <!-- Documents Tab -->
    <div id="documents" class="tab-content" style="display: none;">
        <h3 class="section-title"><i data-lucide="file-text"></i> Identity Documents</h3>
        <div class="doc-grid">
            <div class="doc-card">
                <p class="doc-label">CNIC Front</p>
                <p class="doc-status">{{ $employee->cnic_front_path ? 'Uploaded' : 'Not uploaded' }}</p>
                @if($employee->cnic_front_path)
                    <a href="{{ asset('storage/'.$employee->cnic_front_path) }}" target="_blank" class="doc-link">View</a>
                @endif
            </div>
             <div class="doc-card">
                <p class="doc-label">CNIC Back</p>
                <p class="doc-status">{{ $employee->cnic_back_path ? 'Uploaded' : 'Not uploaded' }}</p>
                 @if($employee->cnic_back_path)
                    <a href="{{ asset('storage/'.$employee->cnic_back_path) }}" target="_blank" class="doc-link">View</a>
                @endif
            </div>
        </div>

        <h3 class="section-title" style="margin-top: 32px;"><i data-lucide="briefcase"></i> Professional Documents</h3>
         <div class="doc-card full-width">
            <p class="doc-label">CV/Resume</p>
            <p class="doc-status">{{ $employee->cv_path ? 'Uploaded' : 'Not uploaded' }}</p>
             @if($employee->cv_path)
                <a href="{{ asset('storage/'.$employee->cv_path) }}" target="_blank" class="doc-link">View</a>
            @endif
        </div>
        <div class="doc-card full-width" style="margin-top: 16px;">
            <p class="doc-label">Educational Documents</p>
            <p class="doc-status">{{ $employee->transcript_path ? 'Uploaded' : 'Not uploaded' }}</p>
             @if($employee->transcript_path)
                <a href="{{ asset('storage/'.$employee->transcript_path) }}" target="_blank" class="doc-link">View</a>
            @endif
        </div>
    </div>
    
    <!-- Activity Logs (Placeholder) -->
    <div id="activity" class="tab-content" style="display: none;">
        <p style="color: #6b7280; font-style: italic;">No activity logs recorded yet.</p>
    </div>

</div>

<!-- Edit Employee Modal (same as index) -->
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
                            <label>Department *</label>
                            <select name="department_id" id="edit_department_id" required>
                                <option value="">Select Department</option>
                            </select>
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
                            <select name="status" id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="invited">Invited</option>
                                <option value="pending_approval">Pending Approval</option>
                            </select>
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
                            <label>Bank Name</label>
                            <input type="text" name="bank_name" id="edit_bank_name">
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
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
        document.getElementById(tabId).style.display = 'block';
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
    }

    function editEmployee(id) {
        fetch(`/employees/${id}/edit`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            const employee = data.employee;
            const departments = data.departments;
            document.getElementById('editEmployeeForm').action = `/employees/${id}`;
            const deptSelect = document.getElementById('edit_department_id');
            deptSelect.innerHTML = '<option value="">Select Department</option>';
            departments.forEach(dept => {
                const opt = document.createElement('option');
                opt.value = dept.id;
                opt.textContent = dept.name;
                if (employee.department_id == dept.id) opt.selected = true;
                deptSelect.appendChild(opt);
            });

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
            document.getElementById('edit_payroll_status').value = employee.payroll_status || '';
            document.getElementById('edit_status').value = employee.status || '';
            document.getElementById('edit_hr_comments').value = employee.hr_comments || '';
            
            if (employee.bank_name) {
                document.getElementById('edit_bankToggle').value = 'Yes';
                document.getElementById('edit_bankFields').style.display = 'grid';
            } else {
                document.getElementById('edit_bankToggle').value = 'No';
                document.getElementById('edit_bankFields').style.display = 'none';
            }
            document.getElementById('edit_bank_name').value = employee.bank_name || '';
            document.getElementById('edit_bank_account_title').value = employee.bank_account_title || '';
            document.getElementById('edit_bank_account_number').value = employee.bank_account_number || '';
            document.getElementById('edit_iban').value = employee.iban || '';
            document.getElementById('edit_banking_comments').value = employee.banking_comments || '';

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

            document.getElementById('edit_cnic_front_status').innerHTML = employee.cnic_front_path ? '<small style="color: green">Uploaded</small>' : '';
            document.getElementById('edit_cnic_back_status').innerHTML = employee.cnic_back_path ? '<small style="color: green">Uploaded</small>' : '';
            document.getElementById('edit_cv_status').innerHTML = employee.cv_path ? '<small style="color: green">Uploaded</small>' : '';
            document.getElementById('edit_transcript_status').innerHTML = employee.transcript_path ? '<small style="color: green">Uploaded</small>' : '';

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
        fields.style.display = (val === 'Yes') ? 'grid' : 'none';
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

    function updateStatus(id, status) {
        if (!confirm(`Are you sure you want to mark this employee as ${status}?`)) return;

        fetch(`/employees/${id}/status`, {
            method: 'PATCH',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update status.');
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
                window.location.href = "{{ route('employees.index') }}";
            } else {
                alert('Failed to delete employee.');
            }
        });
    }

    function approveEmployee(id) {
        if (!confirm('Are you sure you want to approve this employee?')) return;
        fetch(`/employees/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => location.reload());
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

    // Close on click outside
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('editEmployeeModal')) {
            closeEditModal();
        }
    });
</script>

<style>
    .tab-btn {
        background: none;
        border: none;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
    }
    .tab-btn.active {
        color: #111827;
        border-bottom-color: #000;
    }
    .tab-btn:hover {
        color: #111827;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { width: 18px; height: 18px; }

    .info-grid {
        display: grid;
        gap: 24px;
    }
    .info-grid.two-col {
        grid-template-columns: 1fr 1fr;
    }
    
    .info-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .info-item label {
        font-size: 13px;
        color: #9ca3af;
    }
    .info-item p, .value-with-icon {
        font-size: 15px;
        color: #111827;
        font-weight: 500;
    }
    .value-with-icon {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .value-with-icon i { width: 16px; height: 16px; color: #9ca3af; }

    .doc-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }
    .doc-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
    }
    .doc-label {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 4px 0;
    }
    .doc-status {
        font-size: 14px;
        color: #9ca3af;
        margin: 0;
    }
    .doc-link {
        font-size: 13px;
        color: #FF4A00;
        text-decoration: none;
        margin-top: 8px;
        display: inline-block;
    }
    .doc-link:hover { text-decoration: underline; }
</style>
@endsection
