<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Bank;
use App\Models\SmtpConfiguration;
use App\Models\CompanyPolicy;
use App\Models\Setting;
use App\Services\EmployeeIdService;
use App\Services\TaxFormulaService;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    public function index(EmployeeIdService $employeeIdService, TaxFormulaService $taxFormulaService)
    {
        $banks = Bank::orderBy('name')->get();
        $smtpConfigs = SmtpConfiguration::all();
        $defaultSmtp = SmtpConfiguration::where('is_default', true)->first();
        $policies = CompanyPolicy::orderBy('sort_order')->get();
        
        // General Settings
        $employeeIdPrefix = $employeeIdService->employeeIdPrefix();
        $employeeIdCounter = $employeeIdService->currentCounter();
        $nextEmployeeId = $employeeIdService->nextEmployeeIdPreview();
        $hrEmails = Setting::where('key', 'hr_notification_emails')->value('value') ?? '';
        $officeLocation = Setting::where('key', 'office_location')->value('value') ?? '';
        $hrContact = Setting::where('key', 'hr_contact')->value('value') ?? '';
        $taxFormulaConfig = $taxFormulaService->configuration();
        $taxFormulaVariables = $taxFormulaService->availableVariables();
        
        return view('settings.index', compact(
            'banks', 
            'smtpConfigs', 
            'defaultSmtp', 
            'policies',
            'employeeIdPrefix',
            'employeeIdCounter',
            'nextEmployeeId',
            'hrEmails',
            'officeLocation',
            'hrContact',
            'taxFormulaConfig',
            'taxFormulaVariables'
        ));
    }

    // Bank Management
    public function storeBank(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:banks,code',
        ]);

        Bank::create($validated);

        return response()->json(['success' => true, 'message' => 'Bank added successfully.']);
    }

    public function updateBank(Request $request, Bank $bank)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:banks,code,' . $bank->id,
        ]);

        $bank->update($validated);

        return response()->json(['success' => true, 'message' => 'Bank updated successfully.']);
    }

    public function destroyBank(Bank $bank)
    {
        $bank->delete();
        return response()->json(['success' => true, 'message' => 'Bank deleted successfully.']);
    }

    // SMTP Management
    public function storeSmtp(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'host' => 'required|string',
            'port' => 'required|integer',
            'encryption' => 'nullable|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'from_email' => 'required|email',
            'from_name' => 'required|string',
            'is_default' => 'boolean',
        ]);

        if ($request->is_default) {
            SmtpConfiguration::where('is_default', true)->update(['is_default' => false]);
        }

        SmtpConfiguration::create($validated);

        return response()->json(['success' => true, 'message' => 'SMTP Configuration added successfully.']);
    }

    public function updateSmtp(Request $request, SmtpConfiguration $smtp)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'host' => 'required|string',
            'port' => 'required|integer',
            'encryption' => 'nullable|string',
            'username' => 'required|string',
            'password' => 'required|string',
            'from_email' => 'required|email',
            'from_name' => 'required|string',
            'is_default' => 'boolean',
        ]);

        if ($request->is_default) {
            SmtpConfiguration::where('is_default', true)->where('id', '!=', $smtp->id)->update(['is_default' => false]);
        }

        $smtp->update($validated);

        return response()->json(['success' => true, 'message' => 'SMTP Configuration updated successfully.']);
    }

    public function destroySmtp(SmtpConfiguration $smtp)
    {
        $smtp->delete();
        return response()->json(['success' => true, 'message' => 'SMTP Configuration deleted successfully.']);
    }

    public function setDefaultSmtp(SmtpConfiguration $smtp)
    {
        SmtpConfiguration::where('is_default', true)->update(['is_default' => false]);
        $smtp->update(['is_default' => true]);
        
        return response()->json(['success' => true, 'message' => 'Default SMTP updated.']);
    }

    public function sendTestEmail(Request $request, \App\Services\MailService $mailService)
    {
        $request->validate([
            'email' => 'required|email',
            'smtp_id' => 'nullable|exists:smtp_configurations,id',
            'config' => 'nullable|array'
        ]);
        
        try {
            $smtp = null;

            // 1. Try to use unsaved config from modal
            if ($request->has('config')) {
                $smtp = new SmtpConfiguration($request->config);
                // Ensure port is an integer
                $smtp->port = (int)$smtp->port;
            } 
            // 2. Use existing config record
            elseif ($request->has('smtp_id')) {
                $smtp = SmtpConfiguration::find($request->smtp_id);
            } 
            // 3. Fallback to default
            else {
                $smtp = SmtpConfiguration::where('is_default', true)->first();
            }

            if (!$smtp) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No SMTP configuration found to test. Please add one first.'
                ], 400);
            }

            $mailService->sendTestEmail($request->email, $smtp);

            // Log Success
            \App\Models\ActivityLog::create([
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'description' => "Sent SMTP test email to {$request->email} using '{$smtp->name}'",
                'type' => 'success',
                'subject_id' => $smtp->id,
                'subject_type' => get_class($smtp),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['success' => true, 'message' => "Test email sent successfully to {$request->email} using '{$smtp->name}'."]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SMTP Test Failed: ' . $e->getMessage());
            
            // Log Failure
            \App\Models\ActivityLog::create([
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'description' => "Failed to send SMTP test email: " . $e->getMessage(),
                'type' => 'error',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false, 
                'message' => 'Failed to send email: ' . $e->getMessage() . '. Please check your SMTP settings and server logs.'
            ], 500);
        }
    }

    // Policy Management
    public function storePolicy(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_visible' => 'boolean',
        ]);

        $maxOrder = CompanyPolicy::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        CompanyPolicy::create($validated);

        return response()->json(['success' => true, 'message' => 'Policy section added successfully.']);
    }

    public function updatePolicy(Request $request, CompanyPolicy $policy)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_visible' => 'boolean',
        ]);

        $policy->update($validated);

        return response()->json(['success' => true, 'message' => 'Policy section updated successfully.']);
    }

    public function destroyPolicy(CompanyPolicy $policy)
    {
        $policy->delete();
        return response()->json(['success' => true, 'message' => 'Policy section deleted successfully.']);
    }

    public function togglePolicyVisibility(CompanyPolicy $policy)
    {
        $policy->update(['is_visible' => !$policy->is_visible]);
        return response()->json(['success' => true, 'message' => 'Visibility updated.']);
    }

    public function reorderPolicy(Request $request, CompanyPolicy $policy)
    {
        $direction = $request->direction; // 'up' or 'down'
        $currentOrder = $policy->sort_order;

        if ($direction === 'up') {
            $swapWith = CompanyPolicy::where('sort_order', '<', $currentOrder)->orderBy('sort_order', 'desc')->first();
        } else {
            $swapWith = CompanyPolicy::where('sort_order', '>', $currentOrder)->orderBy('sort_order', 'asc')->first();
        }

        if ($swapWith) {
            $newOrder = $swapWith->sort_order;
            $swapWith->update(['sort_order' => $currentOrder]);
            $policy->update(['sort_order' => $newOrder]);
        }

        return response()->json(['success' => true]);
    }

    // General Settings
    public function updateGeneralSettings(Request $request, EmployeeIdService $employeeIdService)
    {
        if ($request->has('employee_id_prefix')) {
            Setting::updateOrCreate(['key' => 'employee_id_prefix'], ['value' => $request->employee_id_prefix]);
        }

        if ($request->boolean('reset_employee_id_counter')) {
            $employeeIdService->resetCounter();
        }

        if ($request->has('hr_notification_emails')) {
            Setting::updateOrCreate(['key' => 'hr_notification_emails'], ['value' => $request->hr_notification_emails]);
        }

        if ($request->has('office_location')) {
            Setting::updateOrCreate(['key' => 'office_location'], ['value' => $request->office_location]);
        }

        if ($request->has('hr_contact')) {
            Setting::updateOrCreate(['key' => 'hr_contact'], ['value' => $request->hr_contact]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'employeeIdPrefix' => $employeeIdService->employeeIdPrefix(),
            'employeeIdCounter' => $employeeIdService->currentCounter(),
            'nextEmployeeId' => $employeeIdService->nextEmployeeIdPreview(),
        ]);
    }

    public function updateTaxFormulas(Request $request, TaxFormulaService $taxFormulaService)
    {
        $validated = $request->validate([
            'taxable_income_formula' => 'required|string|max:1000',
            'slabs' => 'required|array|min:1',
            'slabs.*.label' => 'nullable|string|max:255',
            'slabs.*.min' => 'required|numeric|min:0',
            'slabs.*.max' => 'nullable|numeric|min:0',
            'slabs.*.formula' => 'required|string|max:1000',
        ]);

        $savedConfig = $taxFormulaService->saveConfiguration($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tax calculation rules updated successfully.',
            'configuration' => $savedConfig,
        ]);
    }
}
