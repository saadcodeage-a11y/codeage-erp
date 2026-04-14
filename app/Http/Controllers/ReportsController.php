<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\ReportExportService;
use App\Services\ReportsService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request, ReportsService $reportsService)
    {
        $tabs = $reportsService->tabs();
        $activeTab = array_key_exists((string) $request->get('tab'), $tabs)
            ? (string) $request->get('tab')
            : 'tax';

        $report = $reportsService->build($activeTab, $request->all());

        return view('reports.index', [
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'report' => $report,
            'departments' => $reportsService->departments(),
            'employees' => Employee::query()
                ->with('department')
                ->orderByRaw("CASE WHEN employee_id IS NULL OR employee_id = '' THEN 1 ELSE 0 END")
                ->orderByRaw('LENGTH(employee_id)')
                ->orderBy('employee_id')
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'employee_id', 'designation', 'department_id']),
        ]);
    }

    public function downloadCsv(Request $request, string $reportType, ReportsService $reportsService, ReportExportService $reportExportService)
    {
        abort_unless(array_key_exists($reportType, $reportsService->tabs()), 404);

        $report = $reportsService->build($reportType, $request->all());

        return $reportExportService->downloadCsv(
            $report['csv']['filename'],
            $report['csv']['headers'],
            $report['csv']['rows']
        );
    }

    public function downloadPdf(Request $request, string $reportType, ReportsService $reportsService, ReportExportService $reportExportService)
    {
        abort_unless(array_key_exists($reportType, $reportsService->tabs()), 404);

        $report = $reportsService->build($reportType, $request->all());

        return $reportExportService->downloadPdf($report['pdf']['filename'], [
            'title' => $report['title'],
            'description' => $report['description'],
            'subtitle' => $report['pdf']['subtitle'] ?? null,
            'summaryCards' => $report['summary_cards'] ?? [],
            'secondarySummaryCards' => $report['secondary_summary_cards'] ?? [],
            'table' => $report['table'] ?? null,
            'sections' => $report['sections'] ?? [],
            'filterSummary' => $report['pdf']['filter_summary'] ?? [],
        ]);
    }
}
