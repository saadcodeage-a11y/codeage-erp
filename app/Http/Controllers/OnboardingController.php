<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Bank;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding form
     */
    public function show($token)
    {
        if ($token === 'preview') {
            $employee = new Employee([
                'full_name' => '',
                'email' => 'preview@example.com',
                'onboarding_token' => 'preview'
            ]);
        } else {
            $employee = Employee::where('onboarding_token', $token)->firstOrFail();
            
            // Re-access prevention: if already completed, show the submitted view
            if ($employee->onboarding_completed_at) {
                return view('onboarding.submitted');
            }
        }

        $banks = Bank::where('is_active', true)->orderBy('name')->get();
        
        // Get company policy from settings
        $policy = Setting::where('key', 'company_policy')->first();
        $policyText = $policy ? $policy->value : $this->getDefaultPolicy();

        return view('onboarding.form', [
            'employee' => $employee,
            'banks' => $banks,
            'policy' => $policyText,
            'token' => $token,
            'isPreview' => $token === 'preview'
        ]);
    }

    /**
     * Submit the onboarding form
     */
    public function submit(Request $request, $token)
    {
        $employee = Employee::where('onboarding_token', $token)
            ->whereNull('onboarding_completed_at')
            ->firstOrFail();

        // Validate all steps
        $validated = $request->validate([
            // Step 1: Personal Information
            'full_name' => 'required|string|max:255',
            'phone' => 'required|digits:11',
            'cnic' => 'required|digits:13',
            'gender' => 'required|in:male,female,other',
            'dob' => 'required|date',
            'father_name' => 'required|string|max:255',
            'guardian_contact' => 'required|digits:11',
            'current_address' => 'required|string',
            'permanent_address' => 'required|string',
            'education_level' => 'required|string',
            'field_of_study' => 'nullable|string|max:255',
            'profile_picture' => 'required|image|max:10240',

            // Step 2: Documents
            'cnic_front' => 'required|file|mimes:png,jpg,jpeg,pdf|max:10240',
            'cnic_back' => 'required|file|mimes:png,jpg,jpeg,pdf|max:10240',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'transcript' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:10240',

            // Step 3: Banking (conditional)
            'has_bank_account' => 'nullable|boolean',
            'bank_id' => 'required_if:has_bank_account,1|nullable|exists:banks,id',
            'bank_account_title' => 'required_if:has_bank_account,1|nullable|string|max:255',
            'iban' => 'required_if:has_bank_account,1|nullable|string|max:34',

            // Step 4: Policy & Signature
            'policy_accepted' => 'required|accepted',
            'signature' => 'required|string', // Base64 image
        ]);

        // Create storage directory
        $storagePath = "employees/{$employee->id}";
        Storage::disk('public')->makeDirectory($storagePath);

        // Handle file uploads
        $profilePath = $request->file('profile_picture')->store($storagePath, 'public');
        $cnicFrontPath = $request->file('cnic_front')->store($storagePath, 'public');
        $cnicBackPath = $request->file('cnic_back')->store($storagePath, 'public');
        $cvPath = $request->file('cv')->store($storagePath, 'public');
        $transcriptPath = $request->hasFile('transcript') 
            ? $request->file('transcript')->store($storagePath, 'public') 
            : null;

        // Handle signature (base64 to image)
        $signatureData = $request->input('signature');
        $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
        $signatureData = base64_decode($signatureData);
        $signaturePath = "{$storagePath}/signature.png";
        Storage::disk('public')->put($signaturePath, $signatureData);

        // Update employee record
        $employee->update([
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'cnic' => $validated['cnic'],
            'gender' => $validated['gender'],
            'dob' => $validated['dob'],
            'father_name' => $validated['father_name'],
            'guardian_contact' => $validated['guardian_contact'],
            'current_address' => $validated['current_address'],
            'permanent_address' => $validated['permanent_address'],
            'education_level' => $validated['education_level'],
            'field_of_study' => $validated['field_of_study'] ?? null,
            'profile_picture' => $profilePath,
            'cnic_front_path' => $cnicFrontPath,
            'cnic_back_path' => $cnicBackPath,
            'cv_path' => $cvPath,
            'transcript_path' => $transcriptPath,
            'bank_id' => $validated['bank_id'] ?? null,
            'bank_account_title' => $validated['bank_account_title'] ?? null,
            'iban' => $validated['iban'] ?? null,
            'signature_path' => $signaturePath,
            'policy_accepted_at' => now(),
            'onboarding_completed_at' => now(),
            'status' => 'pending_approval',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding completed successfully! Redirecting...',
            'redirect_url' => route('onboarding.completed')
        ]);
    }

    /**
     * Show the completion page
     */
    public function completed()
    {
        return view('onboarding.submitted');
    }

    /**
     * Default company policy text
     */
    private function getDefaultPolicy(): string
    {
        return <<<EOT
CODEAGE PRIVATE LIMITED - EMPLOYEE AGREEMENT

1. EMPLOYMENT TERMS
By accepting this agreement, you acknowledge that your employment with CodeAge Private Limited is subject to the terms and conditions outlined herein and in your offer letter.

2. CONFIDENTIALITY
You agree to maintain strict confidentiality regarding all company information, trade secrets, client data, and proprietary systems during and after your employment.

3. CODE OF CONDUCT
You will conduct yourself professionally and ethically at all times, treating colleagues, clients, and partners with respect and dignity.

4. INFORMATION ACCURACY
You certify that all information provided during the onboarding process is true, accurate, and complete to the best of your knowledge.

5. DATA PRIVACY
Your personal data will be processed in accordance with applicable data protection laws and our internal privacy policy.

6. TERMINATION
Either party may terminate the employment relationship subject to the notice period specified in your offer letter and applicable labor laws.

By signing below, you acknowledge that you have read, understood, and agree to comply with these terms and conditions.
EOT;
    }

    /**
     * Generate onboarding token for an employee
     */
    public static function generateToken(Employee $employee): string
    {
        $token = Str::random(64);
        $employee->update(['onboarding_token' => $token]);
        return $token;
    }
}
