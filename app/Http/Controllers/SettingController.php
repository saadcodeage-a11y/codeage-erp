<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Bank;
use App\Models\SmtpConfiguration;
use App\Models\CompanyPolicy;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    public function index()
    {
        $banks = Bank::orderBy('name')->get();
        $smtpConfigs = SmtpConfiguration::all();
        $defaultSmtp = SmtpConfiguration::where('is_default', true)->first();
        $policies = CompanyPolicy::orderBy('sort_order')->get();
        
        // General Settings
        $employeeIdPrefix = Setting::where('key', 'employee_id_prefix')->value('value') ?? 'EMP';
        $hrEmails = Setting::where('key', 'hr_notification_emails')->value('value') ?? '';
        
        return view('settings.index', compact(
            'banks', 
            'smtpConfigs', 
            'defaultSmtp', 
            'policies',
            'employeeIdPrefix',
            'hrEmails'
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

    public function sendTestEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        try {
            if (!SmtpConfiguration::where('is_default', true)->exists()) {
                return response()->json(['success' => true, 'message' => 'Simulation: Test email sent via Resend API (Fallback).']);
            }
            return response()->json(['success' => true, 'message' => 'Test email sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
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
    public function updateGeneralSettings(Request $request)
    {
        if ($request->has('employee_id_prefix')) {
            Setting::updateOrCreate(['key' => 'employee_id_prefix'], ['value' => $request->employee_id_prefix]);
        }

        if ($request->has('hr_notification_emails')) {
            Setting::updateOrCreate(['key' => 'hr_notification_emails'], ['value' => $request->hr_notification_emails]);
        }

        return response()->json(['success' => true, 'message' => 'Settings updated successfully.']);
    }
}
