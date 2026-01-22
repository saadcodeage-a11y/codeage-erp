<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\FormTemplate;
use App\Models\SmtpConfiguration;
use App\Services\TemplateVariableService;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'hr');
        $search = $request->get('search');

        $hrTemplatesCount = EmailTemplate::where('category', 'hr')->count();
        $accountsTemplatesCount = EmailTemplate::where('category', 'accounts')->count();
        $generalTemplatesCount = EmailTemplate::where('category', 'general')->count();
        $formsCount = FormTemplate::count();

        $data = [];
        if (in_array($tab, ['hr', 'accounts', 'general'])) {
            $query = EmailTemplate::where('category', $tab);
            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }
            $data = $query->get();
        } elseif ($tab === 'forms') {
            $query = FormTemplate::query();
            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }
            $data = $query->get();
        }

        return view('templates.index', [
            'activeTab' => $tab,
            'templates' => $data,
            'smtpConfigs' => SmtpConfiguration::all(),
            'systemVariables' => TemplateVariableService::getAvailableVariables(),
            'counts' => [
                'hr' => $hrTemplatesCount,
                'accounts' => $accountsTemplatesCount,
                'general' => $generalTemplatesCount,
                'forms' => $formsCount,
            ]
        ]);
    }

    public function storeEmailTemplate(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'nullable|string',
            'variables' => 'nullable|string',
            'smtp_config_id' => 'nullable|exists:smtp_configurations,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        EmailTemplate::create($validated);

        return response()->json(['success' => true, 'message' => 'Email template created successfully.']);
    }

    public function toggleStatus(Request $request, $id, $type)
    {
        $model = $type === 'form' ? FormTemplate::findOrFail($id) : EmailTemplate::findOrFail($id);
        $model->update(['is_active' => !$model->is_active]);

        return response()->json(['success' => true, 'is_active' => $model->is_active]);
    }

    public function updateEmailTemplate(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|string',
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'nullable|string',
            'variables' => 'nullable|string',
            'smtp_config_id' => 'nullable|exists:smtp_configurations,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $template->update($validated);

        return response()->json(['success' => true, 'message' => 'Email template updated successfully.']);
    }

    public function updateFormTemplate(Request $request, $id)
    {
        $template = FormTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'smtp_config_id' => 'nullable|exists:smtp_configurations,id',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $template->update($validated);

        return response()->json(['success' => true, 'message' => 'Form template updated successfully.']);
    }
}
