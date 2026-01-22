<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;

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
            'profile_picture' => 'nullable|image|max:2048',
            'cnic_front' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
            'cnic_back' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'transcript' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        $data = $request->except(['profile_picture', 'cnic_front', 'cnic_back', 'cv', 'transcript']);
        $data['status'] = $request->input('status', 'active');
        
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
        return view('employees.show', compact('employee'));
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
            'profile_picture' => 'nullable|image|max:2048',
            'cnic_front' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
            'cnic_back' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'transcript' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        $data = $request->except(['profile_picture', 'cnic_front', 'cnic_back', 'cv', 'transcript']);
        
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
            'status' => 'required|in:active,inactive,invited,pending_approval'
        ]);

        $employee->update(['status' => $request->status]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
        }

        return back()->with('success', 'Status updated successfully.');
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
