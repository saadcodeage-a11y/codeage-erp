<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Onboarding - CodeAge ERP</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { font-size: 28px; font-weight: 700; color: #111827; margin-bottom: 8px; }
        .header p { color: #6b7280; font-size: 14px; }
        
        /* Progress Bar */
        .progress-container { display: flex; justify-content: center; margin-bottom: 40px; }
        .progress-steps { display: flex; gap: 0; }
        .progress-step { display: flex; flex-direction: column; align-items: center; position: relative; min-width: 140px; }
        .progress-step .step-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e5e7eb; color: #9ca3af; font-weight: 600; transition: all 0.3s; }
        .progress-step.active .step-icon { background: linear-gradient(135deg, #f97316, #dc2626); color: white; }
        .progress-step.completed .step-icon { background: #10b981; color: white; }
        .progress-step .step-label { margin-top: 8px; font-size: 12px; color: #6b7280; font-weight: 500; }
        .progress-step.active .step-label { color: #f97316; font-weight: 600; }
        .progress-line { position: absolute; top: 24px; left: 74px; width: 66px; height: 3px; background: #e5e7eb; }
        .progress-step.completed .progress-line { background: #10b981; }
        .progress-step:last-child .progress-line { display: none; }
        
        /* Form Card */
        .form-card { background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .step-header { padding: 24px 32px; border-bottom: 1px solid #f3f4f6; }
        .step-header h2 { font-size: 20px; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 12px; }
        .step-header h2 i { color: #f97316; }
        .step-content { padding: 32px; display: none; }
        .step-content.active { display: block; }
        
        /* Form Elements */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group select, .form-group textarea { padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1); }
        .form-group input.locked { background: #f9fafb; cursor: not-allowed; }
        .form-group small { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .form-group .error { color: #ef4444; font-size: 12px; margin-top: 4px; display: none; }
        
        /* Radio Group */
        .radio-group { display: flex; gap: 16px; margin-top: 6px; }
        .radio-option { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .radio-option input[type="radio"] { width: 18px; height: 18px; accent-color: #f97316; }
        
        /* File Upload */
        .file-upload { border: 2px dashed #e5e7eb; border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .file-upload:hover { border-color: #f97316; background: #fff7ed; }
        .file-upload.has-file { border-color: #10b981; background: #ecfdf5; }
        .file-upload i { color: #9ca3af; margin-bottom: 8px; }
        .file-upload.has-file i { color: #10b981; }
        .file-upload p { font-size: 14px; color: #6b7280; }
        .file-upload .filename { color: #10b981; font-weight: 500; }
        .file-upload input { display: none; }
        
        /* Photo Upload */
        .photo-upload { display: flex; flex-direction: column; align-items: center; gap: 16px; }
        .photo-preview { width: 150px; height: 150px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 3px solid #e5e7eb; }
        .photo-preview img { width: 100%; height: 100%; object-fit: cover; }
        .photo-preview i { color: #9ca3af; }
        
        /* Checkbox */
        .checkbox-group { display: flex; align-items: center; gap: 12px; padding: 16px; background: #f9fafb; border-radius: 8px; cursor: pointer; }
        .checkbox-group input { width: 20px; height: 20px; accent-color: #f97316; }
        
        /* Banking Fields */
        .banking-fields { display: none; margin-top: 20px; padding: 20px; background: #f9fafb; border-radius: 12px; }
        .banking-fields.show { display: block; }
        
        /* Policy Section */
        .policy-box { max-height: 300px; overflow-y: auto; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; font-size: 13px; line-height: 1.7; color: #374151; margin-bottom: 20px; }
        
        /* Signature Pad */
        .signature-container { border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px; }
        .signature-canvas { width: 100%; height: 150px; border: 1px solid #e5e7eb; border-radius: 8px; background: white; cursor: crosshair; }
        .signature-actions { display: flex; justify-content: flex-end; margin-top: 12px; }
        .signature-actions button { padding: 8px 16px; background: #f3f4f6; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; }
        
        /* Navigation Buttons */
        .form-navigation { padding: 24px 32px; background: #f9fafb; display: flex; justify-content: space-between; border-top: 1px solid #f3f4f6; }
        .btn { padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-outline { background: white; border: 1px solid #e5e7eb; color: #374151; }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-primary { background: linear-gradient(135deg, #f97316, #dc2626); border: none; color: white; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
        
        /* Info Message */
        .info-message { padding: 16px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; color: #1e40af; font-size: 13px; display: flex; align-items: center; gap: 12px; }
        
        /* Success Modal */
        .success-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .success-modal.show { display: flex; }
        .success-content { background: white; padding: 40px; border-radius: 16px; text-align: center; max-width: 400px; }
        .success-content .icon { width: 64px; height: 64px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .success-content .icon i { color: #10b981; }
        .success-content h3 { font-size: 20px; margin-bottom: 12px; color: #111827; }
        .success-content p { color: #6b7280; font-size: 14px; }
        
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .progress-step { min-width: 80px; }
            .progress-step .step-label { font-size: 10px; }
            .progress-line { width: 30px; left: 55px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Employee Onboarding</h1>
            <p>Welcome to CodeAge! Please complete your profile information.</p>
        </div>
        
        <!-- Progress Steps -->
        @if(isset($isPreview) && $isPreview)
        <div style="background: #fff7ed; border: 1px solid #fed7aa; color: #c2410c; padding: 12px; border-radius: 8px; margin-bottom: 24px; text-align: center; font-size: 14px; font-weight: 500;">
            <i data-lucide="eye" style="width: 16px; height: 16px; vertical-align: text-bottom; margin-right: 6px;"></i>
            Preview Mode - Form submission is disabled
        </div>
        @endif

        <div class="progress-container">
            <div class="progress-steps">
                <div class="progress-step active" data-step="1">
                    <div class="step-icon"><i data-lucide="user" style="width: 20px; height: 20px;"></i></div>
                    <span class="step-label">Personal Info</span>
                    <div class="progress-line"></div>
                </div>
                <div class="progress-step" data-step="2">
                    <div class="step-icon"><i data-lucide="file-text" style="width: 20px; height: 20px;"></i></div>
                    <span class="step-label">Documents</span>
                    <div class="progress-line"></div>
                </div>
                <div class="progress-step" data-step="3">
                    <div class="step-icon"><i data-lucide="building-2" style="width: 20px; height: 20px;"></i></div>
                    <span class="step-label">Banking</span>
                    <div class="progress-line"></div>
                </div>
                <div class="progress-step" data-step="4">
                    <div class="step-icon"><i data-lucide="file-check" style="width: 20px; height: 20px;"></i></div>
                    <span class="step-label">Policy</span>
                </div>
            </div>
        </div>
        
        <form id="onboardingForm" enctype="multipart/form-data">
            @csrf
            <div class="form-card">
                <!-- Step 1: Personal Information -->
                <div class="step-content active" data-step="1">
                    <div class="step-header">
                        <h2><i data-lucide="user"></i> Personal Information</h2>
                    </div>
                    <div style="padding: 32px;">
                        <!-- Profile Photo -->
                        <div class="form-row full" style="margin-bottom: 30px;">
                            <div class="form-group">
                                <label>Profile Photo <span class="required">*</span></label>
                                <div class="photo-upload">
                                    <div class="photo-preview" id="photoPreview">
                                        <i data-lucide="camera" style="width: 40px; height: 40px;"></i>
                                    </div>
                                    <label for="profilePhoto" class="btn btn-outline" style="cursor: pointer;">
                                        <i data-lucide="upload" style="width: 16px; height: 16px;"></i> Upload Photo
                                    </label>
                                    <input type="file" id="profilePhoto" name="profile_picture" accept="image/*" required>
                                    <small>JPG, PNG, JPEG. Max 5MB</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name <span class="required">*</span></label>
                                <input type="text" name="full_name" value="{{ $employee->full_name ?? '' }}" placeholder="Enter your full name" required {{ !empty($employee->full_name) ? 'readonly class=locked' : '' }}>
                            </div>
                            <div class="form-group">
                                <label>Email Address <span class="required">*</span></label>
                                <input type="email" value="{{ $employee->email }}" class="locked" readonly>
                                <small>This field is pre-filled and cannot be changed</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number <span class="required">*</span></label>
                                <input type="text" name="phone" placeholder="03001234567" maxlength="11" pattern="\d{11}" required>
                                <small>11 digits (e.g., 03001234567)</small>
                            </div>
                            <div class="form-group">
                                <label>CNIC Number <span class="required">*</span></label>
                                <input type="text" name="cnic" placeholder="1234567890123" maxlength="13" pattern="\d{13}" required>
                                <small>13 digits without dashes</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Gender <span class="required">*</span></label>
                                <div class="radio-group">
                                    <label class="radio-option"><input type="radio" name="gender" value="male" checked> Male</label>
                                    <label class="radio-option"><input type="radio" name="gender" value="female"> Female</label>
                                    <label class="radio-option"><input type="radio" name="gender" value="other"> Other</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth <span class="required">*</span></label>
                                <input type="date" name="dob" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Father's Name <span class="required">*</span></label>
                                <input type="text" name="father_name" placeholder="Enter father's name" required>
                            </div>
                            <div class="form-group">
                                <label>Guardian Contact <span class="required">*</span></label>
                                <input type="text" name="guardian_contact" placeholder="03001234567" maxlength="11" pattern="\d{11}" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Current Address <span class="required">*</span></label>
                                <textarea name="current_address" rows="2" placeholder="Enter current address" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Permanent Address <span class="required">*</span></label>
                                <textarea name="permanent_address" rows="2" placeholder="Enter permanent address" required></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Education Level <span class="required">*</span></label>
                                <select name="education_level" required>
                                    <option value="">Select education level</option>
                                    <option value="matric">Matric/O-Levels</option>
                                    <option value="intermediate">Intermediate/A-Levels</option>
                                    <option value="bachelors">Bachelor's Degree</option>
                                    <option value="masters">Master's Degree</option>
                                    <option value="phd">PhD/Doctorate</option>
                                    <option value="diploma">Diploma/Certificate</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Field of Study / Details</label>
                                <input type="text" name="field_of_study" placeholder="e.g., Computer Science, University name">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Documents -->
                <div class="step-content" data-step="2">
                    <div class="step-header">
                        <h2><i data-lucide="file-text"></i> Documents Upload</h2>
                    </div>
                    <div style="padding: 32px;">
                        <p style="color: #6b7280; margin-bottom: 24px;">Upload clear, readable copies of all required documents.</p>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>CNIC Front Side <span class="required">*</span></label>
                                <label class="file-upload" id="cnicFrontUpload">
                                    <i data-lucide="upload-cloud" style="width: 32px; height: 32px;"></i>
                                    <p>Click to upload or drag and drop</p>
                                    <small>PNG, JPG, PDF (Max 5MB)</small>
                                    <input type="file" name="cnic_front" accept=".png,.jpg,.jpeg,.pdf" required>
                                </label>
                            </div>
                            <div class="form-group">
                                <label>CNIC Back Side <span class="required">*</span></label>
                                <label class="file-upload" id="cnicBackUpload">
                                    <i data-lucide="upload-cloud" style="width: 32px; height: 32px;"></i>
                                    <p>Click to upload or drag and drop</p>
                                    <small>PNG, JPG, PDF (Max 5MB)</small>
                                    <input type="file" name="cnic_back" accept=".png,.jpg,.jpeg,.pdf" required>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Curriculum Vitae (CV) <span class="required">*</span></label>
                                <label class="file-upload" id="cvUpload">
                                    <i data-lucide="upload-cloud" style="width: 32px; height: 32px;"></i>
                                    <p>Click to upload or drag and drop</p>
                                    <small>PDF, DOC, DOCX (Max 10MB)</small>
                                    <input type="file" name="cv" accept=".pdf,.doc,.docx" required>
                                </label>
                            </div>
                            <div class="form-group">
                                <label>Education Transcript <small style="color: #9ca3af;">(Optional)</small></label>
                                <label class="file-upload" id="transcriptUpload">
                                    <i data-lucide="upload-cloud" style="width: 32px; height: 32px;"></i>
                                    <p>Click to upload or drag and drop</p>
                                    <small>PNG, JPG, PDF (Max 10MB)</small>
                                    <input type="file" name="transcript" accept=".png,.jpg,.jpeg,.pdf">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Banking Details -->
                <div class="step-content" data-step="3">
                    <div class="step-header">
                        <h2><i data-lucide="building-2"></i> Banking Details</h2>
                    </div>
                    <div style="padding: 32px;">
                        <p style="color: #6b7280; margin-bottom: 24px;">Provide banking information for salary payments (can be completed later).</p>
                        
                        <label class="checkbox-group">
                            <input type="checkbox" id="hasBankAccount" name="has_bank_account" value="1">
                            <div>
                                <strong>I have a bank account</strong>
                                <p style="color: #6b7280; font-size: 13px; margin-top: 4px;">Check this if you want to provide banking details now</p>
                            </div>
                        </label>
                        
                        <div class="info-message" id="noBankMessage" style="margin-top: 20px;">
                            <i data-lucide="info" style="width: 20px; height: 20px;"></i>
                            No problem! You can provide your banking details later when you're ready.
                        </div>
                        
                        <div class="banking-fields" id="bankingFields">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Bank Name <span class="required">*</span></label>
                                    <select name="bank_id" id="bankSelect">
                                        <option value="">Select your bank</option>
                                        @foreach($banks as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->code }})</option>
                                        @endforeach
                                    </select>
                                    @if($banks->isEmpty())
                                        <small style="color: #ef4444;">No banks available - Please contact HR</small>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>Account Title <span class="required">*</span></label>
                                    <input type="text" name="bank_account_title" placeholder="Enter account title" style="text-transform: uppercase;">
                                    <small>Exactly as it appears on your bank account</small>
                                </div>
                            </div>
                            
                            <div class="form-row full">
                                <div class="form-group">
                                    <label>IBAN Number <span class="required">*</span></label>
                                    <input type="text" name="iban" id="ibanInput" placeholder="PK36ABCD0123456789012345" maxlength="24" style="text-transform: uppercase;">
                                    <small>Format: PK followed by 24 characters</small>
                                    <span class="error" id="ibanError">Invalid IBAN format</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 4: Policy & Signature -->
                <div class="step-content" data-step="4">
                    <div class="step-header">
                        <h2><i data-lucide="file-check"></i> Policy & Signature</h2>
                    </div>
                    <div style="padding: 32px;">
                        <div class="form-group" style="margin-bottom: 24px;">
                            <label>Company Policy Agreement</label>
                            <div class="policy-box">{!! nl2br(e($policy)) !!}</div>
                        </div>
                        
                        <label class="checkbox-group" style="margin-bottom: 24px;">
                            <input type="checkbox" name="policy_accepted" id="policyAccepted" required>
                            <div>
                                <strong>I accept the company policy</strong>
                                <p style="color: #6b7280; font-size: 13px; margin-top: 4px;">By checking this, you acknowledge you've read and agree to all terms</p>
                            </div>
                        </label>
                        
                        <div class="form-group">
                            <label>Digital Signature <span class="required">*</span></label>
                            <div class="signature-container">
                                <canvas id="signatureCanvas" class="signature-canvas"></canvas>
                                <input type="hidden" name="signature" id="signatureData">
                                <div class="signature-actions">
                                    <button type="button" onclick="clearSignature()">
                                        <i data-lucide="eraser" style="width: 14px; height: 14px;"></i> Clear
                                    </button>
                                </div>
                                <small style="color: #6b7280;">Draw your signature using your mouse or touchscreen</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="form-navigation">
                    <button type="button" class="btn btn-outline" id="prevBtn" onclick="prevStep()" style="display: none;">
                        <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Previous
                    </button>
                    <div></div>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">
                        Next <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn" style="display: none;">
                        <i data-lucide="check" style="width: 16px; height: 16px;"></i> Submit Application
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Success Modal -->
    <div class="success-modal" id="successModal">
        <div class="success-content">
            <div class="icon"><i data-lucide="check-circle" style="width: 32px; height: 32px;"></i></div>
            <h3>Onboarding Complete!</h3>
            <p>Thank you for completing your profile. HR will review your submission and get back to you soon.</p>
        </div>
    </div>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        let currentStep = 1;
        const totalSteps = 4;
        
        // Step Navigation
        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(s => s.classList.remove('active'));
            document.querySelector(`.step-content[data-step="${step}"]`).classList.add('active');
            
            document.querySelectorAll('.progress-step').forEach((ps, index) => {
                ps.classList.remove('active', 'completed');
                if (index + 1 < step) ps.classList.add('completed');
                if (index + 1 === step) ps.classList.add('active');
            });
            
            document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'inline-flex';
            document.getElementById('nextBtn').style.display = step === totalSteps ? 'none' : 'inline-flex';
            document.getElementById('submitBtn').style.display = step === totalSteps ? 'inline-flex' : 'none';
            
            if (step === 4) {
                setTimeout(resizeCanvas, 100); // Wait for transition/display animation
            }

            lucide.createIcons();
        }
        
        function validateStep(step) {
            const stepContent = document.querySelector(`.step-content[data-step="${step}"]`);
            const requiredFields = stepContent.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value || (field.type === 'checkbox' && !field.checked)) {
                    isValid = false;
                    field.style.borderColor = '#ef4444';
                } else {
                    field.style.borderColor = '#e5e7eb';
                }
            });
            
            // Special validations
            if (step === 3 && document.getElementById('hasBankAccount').checked) {
                const iban = document.getElementById('ibanInput').value;
                if (!iban || iban.length < 5) {
                    document.getElementById('ibanError').textContent = 'Please enter a valid account number/IBAN';
                    document.getElementById('ibanError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('ibanError').style.display = 'none';
                }
            }
            
            if (step === 4) {
                const signatureData = document.getElementById('signatureData').value;
                if (!signatureData) {
                    alert('Please provide your signature');
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        function nextStep() {
            if (!validateStep(currentStep)) {
                alert('Please fill in all required fields');
                return;
            }
            if (currentStep < totalSteps) {
                currentStep++;
                showStep(currentStep);
            }
        }
        
        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }
        
        // File Upload Handlers
        document.querySelectorAll('.file-upload input').forEach(input => {
            input.addEventListener('change', function() {
                const label = this.closest('.file-upload');
                if (this.files.length > 0) {
                    label.classList.add('has-file');
                    label.querySelector('p').innerHTML = `<span class="filename">${this.files[0].name}</span>`;
                }
            });
        });
        
        // Profile Photo Preview
        document.getElementById('profilePhoto').addEventListener('change', function() {
            const preview = document.getElementById('photoPreview');
            if (this.files.length > 0) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.innerHTML = `<img src="${e.target.result}">`;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Bank Account Toggle
        document.getElementById('hasBankAccount').addEventListener('change', function() {
            const bankingFields = document.getElementById('bankingFields');
            const noBankMessage = document.getElementById('noBankMessage');
            
            bankingFields.classList.toggle('show', this.checked);
            noBankMessage.style.display = this.checked ? 'none' : 'flex';
            
            // Toggle required and reset values
            const inputs = bankingFields.querySelectorAll('input, select');
            inputs.forEach(f => {
                if (this.checked) {
                    f.setAttribute('required', '');
                } else {
                    f.removeAttribute('required');
                    f.value = ''; // Clear value
                    f.style.borderColor = '#e5e7eb'; // Reset error border
                }
            });

            if (!this.checked) {
                document.getElementById('ibanError').style.display = 'none';
            }
        });
        
        // IBAN Auto-uppercase
        document.getElementById('ibanInput').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Signature Pad
        const canvas = document.getElementById('signatureCanvas');
        const ctx = canvas.getContext('2d');
        let drawing = false;
        
        function resizeCanvas() {
            const rect = canvas.getBoundingClientRect();
            if (rect.width > 0) {
                canvas.width = rect.width;
                canvas.height = 150;
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
            }
        }
        resizeCanvas();
        window.addEventListener('resize', () => {
            const oldData = canvas.toDataURL();
            resizeCanvas();
            const img = new Image();
            img.onload = () => ctx.drawImage(img, 0, 0);
            img.src = oldData;
        });
        
        const getPos = (e) => {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        };

        canvas.addEventListener('mousedown', e => { 
            drawing = true; 
            const pos = getPos(e);
            ctx.beginPath(); 
            ctx.moveTo(pos.x, pos.y); 
        });
        
        canvas.addEventListener('mousemove', e => { 
            if (drawing) { 
                const pos = getPos(e);
                ctx.lineTo(pos.x, pos.y); 
                ctx.stroke(); 
            }
        });
        
        canvas.addEventListener('mouseup', () => { drawing = false; saveSignature(); });
        canvas.addEventListener('mouseleave', () => { drawing = false; });
        
        // Touch support
        canvas.addEventListener('touchstart', e => { 
            e.preventDefault(); 
            drawing = true; 
            const pos = getPos(e);
            ctx.beginPath(); 
            ctx.moveTo(pos.x, pos.y); 
        });
        
        canvas.addEventListener('touchmove', e => { 
            e.preventDefault(); 
            if (drawing) { 
                const pos = getPos(e);
                ctx.lineTo(pos.x, pos.y); 
                ctx.stroke(); 
            }
        });
        
        canvas.addEventListener('touchend', () => { drawing = false; saveSignature(); });
        
        function saveSignature() {
            const data = canvas.toDataURL('image/png');
            // Check if canvas is blank (all white/transparent)
            // A simple way is to check if it's the same as a blank canvas
            const blank = document.createElement('canvas');
            blank.width = canvas.width;
            blank.height = canvas.height;
            if (data === blank.toDataURL()) {
                document.getElementById('signatureData').value = '';
            } else {
                document.getElementById('signatureData').value = data;
            }
        }
        
        function clearSignature() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            document.getElementById('signatureData').value = '';
        }
        
        // Form Submit
        document.getElementById('onboardingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validateStep(4)) return;

            @if(isset($isPreview) && $isPreview)
                alert('This is a preview. Form submission is disabled.');
                return;
            @endif
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="animate-spin"></i> Submitting...';
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('{{ route("onboarding.submit", $token) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('successModal').classList.add('show');
                    if (data.redirect_url) {
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    }
                } else {
                    throw new Error(data.message || 'Submission failed');
                }
            } catch (error) {
                alert(error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="check"></i> Submit Application';
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
