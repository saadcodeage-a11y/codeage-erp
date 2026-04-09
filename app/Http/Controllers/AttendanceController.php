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
            'attendance_month' => 'required|date_format:Y-m',
            'attendance_file' => 'required|file|extensions:xls,xlsx',
        ], [
            'attendance_file.extensions' => 'The attendance file must use the .xls or .xlsx extension exported by the fingerprint machine.',
        ]);

        $attendanceImport = $attendanceImportService->import(
            $validated['attendance_file'],
            $request->user(),
            $validated['attendance_month']
        );

        $message = $attendanceImport->imported_rows > 0
            ? 'Attendance file imported successfully.'
            : 'Attendance file was uploaded, but no attendance records were imported. Review the import issues below.';

        return redirect()
            ->route('attendance.index', [
                'month' => $attendanceImport->attendance_month,
                'import' => $attendanceImport->id,
            ])
            ->with($attendanceImport->imported_rows > 0 ? 'success' : 'warning', $message);
    }
}
