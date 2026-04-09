<?php

namespace App\Http\Controllers;

use App\Models\AttendanceImport;
use App\Models\OfficialHoliday;
use App\Models\AttendanceRecord;
use App\Models\Setting;
use App\Services\AttendanceImportService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $selectedImportId = $request->integer('import');
        $selectedMonth = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month);

        $recordsQuery = AttendanceRecord::with('employee', 'attendanceImport')
            ->whereYear('attendance_date', (int) $selectedMonth->format('Y'))
            ->whereMonth('attendance_date', (int) $selectedMonth->format('m'));

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

        $lateGraceMinutes = (int) (Setting::where('key', 'attendance_late_grace_minutes')->value('value') ?? 0);
        $officialHolidays = OfficialHoliday::query()
            ->whereYear('holiday_date', (int) $selectedMonth->format('Y'))
            ->orderBy('holiday_date')
            ->get();

        return view('attendance.index', compact(
            'attendanceRecords',
            'month',
            'stats',
            'recentImports',
            'selectedImport',
            'lateGraceMinutes',
            'officialHolidays'
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

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'late_grace_minutes' => 'required|integer|min:0|max:240',
        ]);

        Setting::updateOrCreate(
            ['key' => 'attendance_late_grace_minutes'],
            ['value' => (string) $validated['late_grace_minutes']]
        );

        return redirect()
            ->route('attendance.index', ['month' => $request->get('month', now()->format('Y-m'))])
            ->with('success', 'Attendance rules updated successfully.');
    }

    public function storeHoliday(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'holiday_date' => 'required|date|unique:official_holidays,holiday_date',
            'description' => 'nullable|string|max:1000',
        ]);

        $holidayDate = \Illuminate\Support\Carbon::parse($validated['holiday_date']);

        if ($holidayDate->isWeekend()) {
            return back()
                ->withInput()
                ->withErrors([
                    'holiday_date' => 'Saturday and Sunday are already treated as weekend holidays. Add only official holidays that fall on working days.',
                ]);
        }

        OfficialHoliday::create($validated);

        return redirect()
            ->route('attendance.index', ['month' => $holidayDate->format('Y-m')])
            ->with('success', 'Official holiday added successfully.');
    }

    public function destroyHoliday(Request $request, OfficialHoliday $holiday)
    {
        $month = $request->get('month', $holiday->holiday_date->format('Y-m'));

        $holiday->delete();

        return redirect()
            ->route('attendance.index', ['month' => $month])
            ->with('success', 'Official holiday removed successfully.');
    }
}
