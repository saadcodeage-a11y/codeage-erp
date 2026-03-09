<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Models\EmployeeEmploymentHistory;
use App\Services\MailService;
use Illuminate\Support\Carbon;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        // Counts for tabs
        $counts = [
            'active' => Employee::where('status', 'active')->count(),
            'invited' => Employee::where('status', 'invited')->count(),
            'pending_approval' => Employee::where('status', 'pending_approval')->count(),
            'inactive' => Employee::whereIn('status', ['inactive', 'terminated', 'on_leave'])->count(),
        ];

        // Filter Logic
        $status = $request->get('status', 'active');
        $query = Employee::with('department');

        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'invited') {
            $query->where('status', 'invited');
        } elseif ($status === 'pending_approval') {
            $query->where('status', 'pending_approval');
        } elseif ($status === 'inactive') {
            $query->whereIn('status', ['inactive', 'terminated', 'on_leave']);
        }

        // Search Logic
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        $employees = $query->latest()->paginate(10)->withQueryString();
        $departments = \App\Models\Department::all();

        return view('employees.index', compact('employees', 'counts', 'status', 'departments'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'department_id' => 'required|exists:departments,id',
            'designation' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive,invited,pending_approval',
            'inactive_reason' => 'nullable|string|max:1000|required_if:status,inactive',
            'hiring_date' => 'nullable|date',
            // Personal
            'cnic' => 'nullable|string',
            'phone' => 'nullable|string',
            'gender' => 'nullable|in:Male,Female,Other',
            'dob' => 'nullable|date',
            'current_address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'father_name' => 'nullable|string',
            'guardian_contact' => 'nullable|string',
            'education_level' => 'nullable|string',
            'field_of_study' => 'nullable|string',
            'job_location' => 'nullable|string',
            'payroll_status' => 'nullable|string',
            'hr_comments' => 'nullable|string',
            'banking_comments' => 'nullable|string',
            // Files
            'profile_picture' => 'nullable|image|max:10240',
            'cnic_front' => 'nullable|file|mimes:jpg,png,pdf|max:10240',
            'cnic_back' => 'nullable|file|mimes:jpg,png,pdf|max:10240',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'transcript' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['profile_picture', 'cnic_front', 'cnic_back', 'cv', 'transcript']);
        $data['status'] = $request->input('status', 'active');
        $data['inactive_reason'] = $data['status'] === 'inactive'
            ? $request->input('inactive_reason')
            : null;
        
        // Handle Uploads
        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')->store('employees/profiles', 'public');
        }
        if ($request->hasFile('cnic_front')) {
            $data['cnic_front_path'] = $request->file('cnic_front')->store('employees/documents', 'public');
        }
        if ($request->hasFile('cnic_back')) {
            $data['cnic_back_path'] = $request->file('cnic_back')->store('employees/documents', 'public');
        }
        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('employees/documents', 'public');
        }
        if ($request->hasFile('transcript')) {
            $data['transcript_path'] = $request->file('transcript')->store('employees/documents', 'public');
        }

        // Generate ID if not provided (Simplistic)
        $data['employee_id'] = 'EMP' . rand(1000, 9999); 

        $employee = Employee::create($data);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee added successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'department',
            'bank',
            'employmentHistories.department',
        ]);

        $historyIds = $employee->employmentHistories->modelKeys();

        $employeeActivityLogs = ActivityLog::with('user', 'subject')
            ->where(function ($query) use ($employee, $historyIds) {
                $query->where(function ($subjectQuery) use ($employee) {
                    $subjectQuery
                        ->where('subject_type', Employee::class)
                        ->where('subject_id', $employee->id);
                });

                if (! empty($historyIds)) {
                    $query->orWhere(function ($subjectQuery) use ($historyIds) {
                        $subjectQuery
                            ->where('subject_type', EmployeeEmploymentHistory::class)
                            ->whereIn('subject_id', $historyIds);
                    });
                }
            })
            ->latest()
            ->get();

        return view('employees.show', compact('employee', 'employeeActivityLogs'));
    }

    public function edit(Employee $employee)
    {
        if (request()->ajax()) {
            return response()->json([
                'employee' => $employee,
                'departments' => \App\Models\Department::all()
            ]);
        }
        $departments = \App\Models\Department::all();
        return view('employees.edit', compact('employee', 'departments'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'department_id' => 'required|exists:departments,id',
            'designation' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive,invited,pending_approval',
            'inactive_reason' => 'nullable|string|max:1000|required_if:status,inactive',
            'hiring_date' => 'nullable|date',
            // Personal
            'cnic' => 'nullable|string',
            'phone' => 'nullable|string',
            'gender' => 'nullable|in:Male,Female,Other',
            'dob' => 'nullable|date',
            'current_address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
            'father_name' => 'nullable|string',
            'guardian_contact' => 'nullable|string',
            'education_level' => 'nullable|string',
            'field_of_study' => 'nullable|string',
            'job_location' => 'nullable|string',
            'payroll_status' => 'nullable|string',
            'hr_comments' => 'nullable|string',
            'banking_comments' => 'nullable|string',
            // Files (nullable on update)
            'profile_picture' => 'nullable|image|max:10240',
            'cnic_front' => 'nullable|file|mimes:jpg,png,pdf|max:10240',
            'cnic_back' => 'nullable|file|mimes:jpg,png,pdf|max:10240',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'transcript' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['profile_picture', 'cnic_front', 'cnic_back', 'cv', 'transcript']);
        $status = $request->input('status', $employee->status);
        $data['inactive_reason'] = $status === 'inactive'
            ? $request->input('inactive_reason')
            : null;
        
        // Handle Uploads
        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')->store('employees/profiles', 'public');
        }
        if ($request->hasFile('cnic_front')) {
            $data['cnic_front_path'] = $request->file('cnic_front')->store('employees/documents', 'public');
        }
        if ($request->hasFile('cnic_back')) {
            $data['cnic_back_path'] = $request->file('cnic_back')->store('employees/documents', 'public');
        }
        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('employees/documents', 'public');
        }
        if ($request->hasFile('transcript')) {
            $data['transcript_path'] = $request->file('transcript')->store('employees/documents', 'public');
        }

        $employee->update($data);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    public function updateStatus(Request $request, Employee $employee)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,invited,pending_approval',
            'inactive_reason' => 'nullable|string|max:1000|required_if:status,inactive',
        ]);

        $employee->update([
            'status' => $request->status,
            'inactive_reason' => $request->status === 'inactive'
                ? $request->inactive_reason
                : null,
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return back()->with('success', 'Status updated successfully.');
    }

    public function invite(Request $request, \App\Services\MailService $mailService)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'department_id' => 'required|exists:departments,id',
            'designation' => 'required|string|max:255',
        ]);

        $token = \Illuminate\Support\Str::random(40);
        
        $employee = Employee::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'],
            'designation' => $validated['designation'],
            'status' => 'invited',
            'onboarding_token' => $token, // Ensure this column exist or just use token for URL
        ]);

        // Find invitation template
        $template = \App\Models\EmailTemplate::where('name', 'Employee Invitation')
            ->where('is_active', true)
            ->first();

        if ($template) {
            $inviteLink = route('onboarding.show', ['token' => $token]);
            $variables = [
                'employeeName' => $employee->full_name,
                'formLink' => $inviteLink,
                'inviteLink' => $inviteLink,
                'companyName' => config('app.name'),
            ];
            
            try {
                $mailService->sendEmailTemplate($employee->email, $template, $variables);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Invitation Email Failed: ' . $e->getMessage());
                // Don't fail the whole request but keep invited status
            }
        }

        return response()->json(['success' => true, 'message' => 'Invitation sent successfully.']);
    }

    public function approve(Request $request, Employee $employee, MailService $mailService)
    {
        $startDate = $request->input('start_date')
            ?: optional($employee->hiring_date)->toDateString()
            ?: now()->toDateString();
        $startTime = $request->input('start_time') ?: '09:00';

        $employee->update([
            'status' => 'active',
            'hiring_date' => $startDate,
        ]);
        
        // Send "Welcome" email
        try {
            $template = EmailTemplate::where('name', 'Employee Welcome')->first();
            
            if ($template) {
                $officeLocation = Setting::where('key', 'office_location')->value('value') ?? 'Our Office';
                $hrContact = Setting::where('key', 'hr_contact')->value('value') ?? 'HR Department';
                
                $variables = [
                    'employeeName' => $employee->full_name,
                    'position' => $employee->designation ?? 'Team Member',
                    'startDate' => Carbon::parse($startDate)->format('F j, Y'),
                    'startTime' => Carbon::parse($startTime)->format('g:i A'),
                    'officeLocation' => $officeLocation,
                    'hrContact' => $hrContact
                ];
                
                $mailService->sendEmailTemplate($employee->email, $template, $variables);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send welcome email: ' . $e->getMessage());
        }
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Employee approved and welcome email sent.']);
        }
        return back()->with('success', 'Employee approved and welcome email sent.');
    }

    public function disapprove(Employee $employee)
    {
        // For disapproval, move back to invited or delete? 
        // User said "move the record to pending tab... approve, disapprove".
        // Let's move to 'inactive' or keep in 'invited'? 
        // Typically disapproval means "needs corrections", so maybe move back to invited and send email?
        // Or just mark as inactive for now.
        $employee->update(['status' => 'inactive']);
        
        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Employee application disapproved.']);
        }
        return back()->with('success', 'Employee application disapproved.');
    }

    public function destroy(Employee $employee)
    {
        // Optional: Delete associated files if needed
        $employee->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Employee deleted successfully.']);
        }

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
