<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Models\HrLetter;
use App\Models\LeaveRequest;
use App\Models\EmployeeEmploymentHistory;
use App\Services\EmployeeIdService;
use App\Services\EmployeeImportService;
use App\Services\MailService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    protected const AVAILABLE_STATUSES = [
        'active',
        'inactive',
        'invited',
        'pending_approval',
        'resigned',
        'terminated',
    ];

    public function index(Request $request)
    {
        // Counts for tabs
        $counts = [
            'active' => Employee::where('status', 'active')->count(),
            'invited' => Employee::where('status', 'invited')->count(),
            'pending_approval' => Employee::where('status', 'pending_approval')->count(),
            'inactive' => Employee::whereIn('status', ['inactive', 'terminated', 'resigned', 'on_leave'])->count(),
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
            $query->whereIn('status', ['inactive', 'terminated', 'resigned', 'on_leave']);
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

        $employees = $query
            ->orderByRaw("CASE WHEN employee_id IS NULL OR employee_id = '' THEN 1 ELSE 0 END")
            ->orderByRaw('LENGTH(employee_id)')
            ->orderBy('employee_id')
            ->paginate(10)
            ->withQueryString();
        $departments = \App\Models\Department::all();

        return view('employees.index', compact('employees', 'counts', 'status', 'departments'));
    }


    public function store(Request $request, EmployeeIdService $employeeIdService)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'department_id' => 'required|exists:departments,id',
            'designation' => 'required|string|max:255',
            'status' => 'nullable|in:' . implode(',', self::AVAILABLE_STATUSES),
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
            'shift_start_time' => 'nullable|date_format:H:i|required_with:shift_end_time',
            'shift_end_time' => 'nullable|date_format:H:i|required_with:shift_start_time',
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
        $data['shift_start_time'] = $this->normalizeShiftTime($request->input('shift_start_time'));
        $data['shift_end_time'] = $this->normalizeShiftTime($request->input('shift_end_time'));
        
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

        $employee = Employee::create($data);

        $this->assignEmployeeIdIfNeeded($employee, $employeeIdService);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee added successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'department',
            'bank',
            'employmentHistories.department',
            'leaveRequests.leaveType',
            'hrLetters.generatedBy',
            'attendanceRecords' => fn ($query) => $query->latest('attendance_date')->limit(10),
        ]);

        $historyIds = $employee->employmentHistories->modelKeys();
        $leaveRequestIds = $employee->leaveRequests->modelKeys();
        $letterIds = $employee->hrLetters->modelKeys();

        $employeeActivityLogs = ActivityLog::with('user', 'subject')
            ->where(function ($query) use ($employee, $historyIds, $leaveRequestIds, $letterIds) {
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

                if (! empty($leaveRequestIds)) {
                    $query->orWhere(function ($subjectQuery) use ($leaveRequestIds) {
                        $subjectQuery
                            ->where('subject_type', LeaveRequest::class)
                            ->whereIn('subject_id', $leaveRequestIds);
                    });
                }

                if (! empty($letterIds)) {
                    $query->orWhere(function ($subjectQuery) use ($letterIds) {
                        $subjectQuery
                            ->where('subject_type', HrLetter::class)
                            ->whereIn('subject_id', $letterIds);
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

    public function update(Request $request, Employee $employee, EmployeeIdService $employeeIdService)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'department_id' => 'required|exists:departments,id',
            'designation' => 'required|string|max:255',
            'status' => 'nullable|in:' . implode(',', self::AVAILABLE_STATUSES),
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
            'shift_start_time' => 'nullable|date_format:H:i|required_with:shift_end_time',
            'shift_end_time' => 'nullable|date_format:H:i|required_with:shift_start_time',
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
        $data['shift_start_time'] = $this->normalizeShiftTime($request->input('shift_start_time'));
        $data['shift_end_time'] = $this->normalizeShiftTime($request->input('shift_end_time'));
        
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
        $this->assignEmployeeIdIfNeeded($employee, $employeeIdService);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    public function updateShiftTiming(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'shift_start_time' => 'nullable|date_format:H:i|required_with:shift_end_time',
            'shift_end_time' => 'nullable|date_format:H:i|required_with:shift_start_time',
        ]);

        $employee->update([
            'shift_start_time' => $this->normalizeShiftTime($validated['shift_start_time'] ?? null),
            'shift_end_time' => $this->normalizeShiftTime($validated['shift_end_time'] ?? null),
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Employee working hours updated successfully.');
    }

    public function updateStatus(Request $request, Employee $employee, EmployeeIdService $employeeIdService)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', self::AVAILABLE_STATUSES),
            'inactive_reason' => 'nullable|string|max:1000|required_if:status,inactive',
        ]);

        $employee->update([
            'status' => $request->status,
            'inactive_reason' => $request->status === 'inactive'
                ? $request->inactive_reason
                : null,
        ]);
        $this->assignEmployeeIdIfNeeded($employee, $employeeIdService);

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

    public function importCsv(Request $request, EmployeeImportService $employeeImportService)
    {
        $validator = Validator::make($request->all(), [
            'employee_csv' => 'required|file|extensions:csv',
        ], [
            'employee_csv.extensions' => 'The employee upload file must be a .csv file.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('employees.index')
                ->withErrors($validator, 'employeeImport')
                ->with('open_modal', 'employeeImportModal');
        }

        $summary = $employeeImportService->import($request->file('employee_csv'));
        $message = $summary['imported'] > 0
            ? "{$summary['imported']} employees imported successfully."
            : 'Employee CSV was uploaded, but no employees were imported.';

        return redirect()
            ->route('employees.index')
            ->with($summary['imported'] > 0 ? 'success' : 'warning', $message)
            ->with('employeeImportSummary', $summary);
    }

    public function approve(Request $request, Employee $employee, MailService $mailService, EmployeeIdService $employeeIdService)
    {
        $startDate = $request->input('start_date')
            ?: optional($employee->hiring_date)->toDateString()
            ?: now()->toDateString();
        $startTime = $request->input('start_time') ?: '09:00';

        $employee->update([
            'status' => 'active',
            'hiring_date' => $startDate,
        ]);
        $this->assignEmployeeIdIfNeeded($employee, $employeeIdService);
        
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
        $employee->update([
            'status' => 'inactive',
            'inactive_reason' => 'Application disapproved.',
        ]);
        
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

    public function generateLetter(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'type' => 'required|in:offer,experience,termination',
        ]);

        $letterType = $validated['type'];
        $generatedAt = now();

        $letter = $employee->hrLetters()->create([
            'generated_by_user_id' => $request->user()->id,
            'type' => $letterType,
            'title' => $this->employeeLetterTitle($employee, $letterType),
            'body' => $this->employeeLetterBody($employee, $letterType, $generatedAt),
            'generated_at' => $generatedAt,
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($letterType) . ' letter generated successfully.',
            'download_url' => route('employees.letters.download', [$employee, $letter]),
        ]);
    }

    public function downloadLetter(Employee $employee, HrLetter $letter)
    {
        abort_unless($letter->employee_id === $employee->id, 404);

        $filename = str($letter->title)->slug()->append('.html')->toString();

        return response($letter->body, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function assignEmployeeIdIfNeeded(Employee $employee, EmployeeIdService $employeeIdService): void
    {
        if ($employee->status !== 'active' || $employee->employee_id) {
            return;
        }

        $employee->updateQuietly([
            'employee_id' => $employeeIdService->generateNextEmployeeId(),
        ]);
    }

    protected function employeeLetterTitle(Employee $employee, string $type): string
    {
        return match ($type) {
            'offer' => "Offer Letter - {$employee->full_name}",
            'experience' => "Experience Letter - {$employee->full_name}",
            'termination' => "Termination Letter - {$employee->full_name}",
        };
    }

    protected function normalizeShiftTime(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::createFromFormat('H:i', $value)->format('H:i:s');
    }

    protected function employeeLetterBody(Employee $employee, string $type, Carbon $generatedAt): string
    {
        $companyName = config('app.name');
        $officeLocation = Setting::where('key', 'office_location')->value('value') ?? 'our office';
        $designation = $employee->designation ?? 'Team Member';
        $department = $employee->department?->name ?? 'the assigned department';
        $joiningDate = $employee->hiring_date?->format('F j, Y') ?? 'the assigned joining date';
        $today = $generatedAt->format('F j, Y');

        return match ($type) {
            'offer' => <<<HTML
                <h1>Offer Letter</h1>
                <p>Date: {$today}</p>
                <p>Dear {$employee->full_name},</p>
                <p>We are pleased to offer you the position of <strong>{$designation}</strong> in <strong>{$department}</strong> at <strong>{$companyName}</strong>.</p>
                <p>Your expected start date is <strong>{$joiningDate}</strong> and your work location will be <strong>{$officeLocation}</strong>.</p>
                <p>We look forward to your contribution to the organization.</p>
                <p>Sincerely,<br>{$companyName}</p>
            HTML,
            'experience' => <<<HTML
                <h1>Experience Letter</h1>
                <p>Date: {$today}</p>
                <p>This letter confirms that <strong>{$employee->full_name}</strong> has served at <strong>{$companyName}</strong> as <strong>{$designation}</strong> in <strong>{$department}</strong>.</p>
                <p>The employee joined on <strong>{$joiningDate}</strong> and fulfilled responsibilities assigned during the tenure with professionalism.</p>
                <p>We appreciate the employee's services and wish them success in future endeavors.</p>
                <p>Sincerely,<br>{$companyName}</p>
            HTML,
            'termination' => <<<HTML
                <h1>Termination Letter</h1>
                <p>Date: {$today}</p>
                <p>Dear {$employee->full_name},</p>
                <p>This letter serves as formal notice that your employment with <strong>{$companyName}</strong> in the role of <strong>{$designation}</strong> is concluded effective <strong>{$today}</strong>.</p>
                <p>Please coordinate with HR for final clearance, handover, and settlement requirements.</p>
                <p>We wish you the best in your future endeavors.</p>
                <p>Sincerely,<br>{$companyName}</p>
            HTML,
        };
    }
}
