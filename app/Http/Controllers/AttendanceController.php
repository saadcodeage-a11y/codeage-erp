<?php

namespace App\Http\Controllers;

use App\Models\AttendanceImport;
use App\Models\AttendanceRecord;
use App\Services\AttendanceImportService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $selectedImportId = $request->integer('import');

        $recordsQuery = AttendanceRecord::with('employee', 'attendanceImport')
            ->whereYear('attendance_date', (int) substr($month, 0, 4))
            ->whereMonth('attendance_date', (int) substr($month, 5, 2));

        if ($search = $request->get('search')) {
            $recordsQuery->whereHas('employee', function ($query) use ($search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $attendanceRecords = (clone $recordsQuery)
            ->orderByDesc('attendance_date')
            ->orderBy('employee_id')
            ->paginate(20)
            ->withQueryString();

        $countsBase = clone $recordsQuery;
        $stats = [
            'records' => (clone $countsBase)->count(),
            'present' => (clone $countsBase)->where('status', 'present')->count(),
            'late' => (clone $countsBase)->where('status', 'late')->count(),
            'absent' => (clone $countsBase)->where('status', 'absent')->count(),
            'incomplete' => (clone $countsBase)->where('status', 'incomplete')->count(),
        ];

        $recentImports = AttendanceImport::with('importedBy')
            ->withCount('errors')
            ->latest()
            ->take(10)
            ->get();

        $selectedImport = $selectedImportId
            ? AttendanceImport::with(['errors', 'importedBy'])->find($selectedImportId)
            : $recentImports->first();

        return view('attendance.index', compact(
            'attendanceRecords',
            'month',
            'stats',
            'recentImports',
            'selectedImport'
        ));
    }

    public function import(Request $request, AttendanceImportService $attendanceImportService)
    {
        $validated = $request->validate([
            'attendance_file' => 'required|file|mimes:xls,xlsx',
        ]);

        $attendanceImport = $attendanceImportService->import($validated['attendance_file'], $request->user());
        $firstImportedRecord = $attendanceImport->records()->orderBy('attendance_date')->first();

        return redirect()
            ->route('attendance.index', [
                'month' => $firstImportedRecord?->attendance_date?->format('Y-m') ?? now()->format('Y-m'),
                'import' => $attendanceImport->id,
            ])
            ->with('success', 'Attendance file imported successfully.');
    }
}
