<?php

namespace Tests\Feature;

use App\Models\AttendanceImport;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    protected function createEmployee(array $attributes = []): Employee
    {
        $department = Department::firstOrCreate(['name' => 'Operations']);

        return Employee::create(array_merge([
            'full_name' => 'Attendance Employee',
            'email' => 'attendance-' . uniqid() . '@example.com',
            'status' => 'active',
            'department_id' => $department->id,
            'designation' => 'Coordinator',
            'employee_id' => 'CA-E-' . random_int(100, 999),
            'hiring_date' => now()->subMonth()->toDateString(),
        ], $attributes));
    }

    protected function createAttendanceFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['No.', 'Name', 'Date', 'Clock In', 'Clock Out', 'Late', 'Early', 'Absent', 'Work Time'],
            ...$rows,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'attendance_');
        $xlsPath = $path . '.xls';
        rename($path, $xlsPath);

        $writer = new Xls($spreadsheet);
        $writer->save($xlsPath);

        return new UploadedFile(
            $xlsPath,
            'monthly-attendance.xls',
            'application/vnd.ms-excel',
            null,
            true
        );
    }

    protected function createLegacyMachineExportFile(array $rows): UploadedFile
    {
        $payload = $this->legacyRecord(0x0809, pack('vvv', 0, 0x0010, 0));
        $allRows = [
            ['No.', 'Name', 'Date', 'Clock In', 'Clock Out', 'Late', 'Early', 'Absent', 'Work Time'],
            ...$rows,
        ];

        foreach ($allRows as $rowIndex => $columns) {
            foreach ($columns as $columnIndex => $value) {
                $payload .= $this->legacyLabelRecord($rowIndex, $columnIndex, (string) $value);
            }
        }

        $payload .= $this->legacyRecord(0x000A, '');

        $path = tempnam(sys_get_temp_dir(), 'legacy_attendance_');
        $xlsPath = $path . '.xls';
        rename($path, $xlsPath);
        file_put_contents($xlsPath, $payload);

        return new UploadedFile(
            $xlsPath,
            'machine-export.xls',
            'application/octet-stream',
            null,
            true
        );
    }

    protected function legacyRecord(int $recordId, string $payload): string
    {
        return pack('vv', $recordId, strlen($payload)) . $payload;
    }

    protected function legacyLabelRecord(int $row, int $column, string $value): string
    {
        $encoded = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
        $payload = pack('vvCCC', $row, $column, 0x40, 0x00, 0x00) . chr(strlen($encoded)) . $encoded;

        return $this->legacyRecord(0x0004, $payload);
    }

    public function test_hr_can_import_attendance_xls_and_match_employee_ids(): void
    {
        $employee = $this->createEmployee([
            'employee_id' => 'CA-E-05',
            'full_name' => 'Kumail Abbas',
            'shift_start_time' => '09:00',
            'shift_end_time' => '17:00',
        ]);
        $hrUser = $this->createUser('HR Manager');

        $file = $this->createAttendanceFile([
            ['CA-E-05', 'Kumail Abbas', '02/03/2026', '09:17', '15:06', '00:17', '', '', '05:42'],
            ['CA-E-05', 'Kumail Abbas', '03/03/2026', '08:59', '16:07', '', '', '', '06:00'],
        ]);

        $response = $this->actingAs($hrUser)->post(route('attendance.import'), [
            'attendance_month' => '2026-03',
            'attendance_file' => $file,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('attendance_imports', [
            'source_file_name' => 'monthly-attendance.xls',
            'attendance_month' => '2026-03',
            'imported_rows' => 2,
            'error_rows' => 0,
        ]);

        $firstRecord = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-03-02')
            ->firstOrFail();
        $secondRecord = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-03-03')
            ->firstOrFail();

        $this->assertSame('09:17:00', $firstRecord->clock_in);
        $this->assertSame('15:06:00', $firstRecord->clock_out);
        $this->assertSame('00:17', $firstRecord->late_duration);
        $this->assertSame('05:42', $firstRecord->work_duration);
        $this->assertSame('09:00:00', $firstRecord->shift_start_time);
        $this->assertSame('17:00:00', $firstRecord->shift_end_time);
        $this->assertSame('late', $firstRecord->status);
        $this->assertSame('present', $secondRecord->status);
    }

    public function test_hr_can_import_legacy_machine_export_file_and_match_employee_ids(): void
    {
        $employee = $this->createEmployee([
            'employee_id' => 'CA-E-05',
            'full_name' => 'Kumail Abbas',
            'shift_start_time' => '09:00',
            'shift_end_time' => '17:00',
        ]);
        $hrUser = $this->createUser('HR Manager');

        $file = $this->createLegacyMachineExportFile([
            ['CA-E-05', 'Kumail Abbas', '02/03/2026', '09:17', '15:06', '00:17', '', '', '05:42'],
            ['CA-E-05', 'Kumail Abbas', '03/03/2026', '08:59', '16:07', '', '', '', '06:00'],
        ]);

        $this->actingAs($hrUser)->post(route('attendance.import'), [
            'attendance_month' => '2026-03',
            'attendance_file' => $file,
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_imports', [
            'source_file_name' => 'machine-export.xls',
            'attendance_month' => '2026-03',
            'imported_rows' => 2,
            'error_rows' => 0,
        ]);

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-03-02')
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('09:17:00', $record->clock_in);
        $this->assertSame('15:06:00', $record->clock_out);
    }

    public function test_import_records_unknown_employee_id_as_error(): void
    {
        $hrUser = $this->createUser('HR Manager');
        $file = $this->createAttendanceFile([
            ['CA-E-99', 'Unknown Employee', '02/03/2026', '09:17', '15:06', '00:17', '', '', '05:42'],
        ]);

        $this->actingAs($hrUser)->post(route('attendance.import'), [
            'attendance_month' => '2026-03',
            'attendance_file' => $file,
        ])->assertRedirect();

        $import = AttendanceImport::firstOrFail();

        $this->assertSame(0, $import->imported_rows);
        $this->assertSame(1, $import->error_rows);
        $this->assertDatabaseHas('attendance_import_errors', [
            'attendance_import_id' => $import->id,
            'employee_code' => 'CA-E-99',
            'reason' => 'Employee ID CA-E-99 was not found in the ERP.',
        ]);
    }

    public function test_import_skips_duplicate_attendance_rows_and_surfaces_error(): void
    {
        $employee = $this->createEmployee([
            'employee_id' => 'CA-E-77',
            'full_name' => 'Duplicate Employee',
        ]);
        $hrUser = $this->createUser('HR Manager');

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-03-02',
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
            'status' => 'present',
        ]);

        $file = $this->createAttendanceFile([
            ['CA-E-77', 'Duplicate Employee', '02/03/2026', '09:17', '15:06', '00:17', '', '', '05:42'],
        ]);

        $this->actingAs($hrUser)->post(route('attendance.import'), [
            'attendance_month' => '2026-03',
            'attendance_file' => $file,
        ])->assertRedirect();

        $import = AttendanceImport::firstOrFail();

        $this->assertSame(0, $import->imported_rows);
        $this->assertSame(1, $import->duplicate_rows);
        $this->assertDatabaseHas('attendance_import_errors', [
            'attendance_import_id' => $import->id,
            'employee_code' => 'CA-E-77',
            'reason' => 'Attendance for this employee and date already exists in the system.',
        ]);
    }

    public function test_accounts_manager_cannot_access_attendance_module(): void
    {
        $accountsUser = $this->createUser('Accounts Manager');

        $this->actingAs($accountsUser)->get(route('attendance.index'))->assertForbidden();
    }

    public function test_import_records_error_when_row_month_does_not_match_selected_month(): void
    {
        $employee = $this->createEmployee([
            'employee_id' => 'CA-E-11',
            'full_name' => 'Month Check Employee',
        ]);
        $hrUser = $this->createUser('HR Manager');
        $file = $this->createAttendanceFile([
            ['CA-E-11', 'Month Check Employee', '02/04/2026', '09:17', '15:06', '00:17', '', '', '05:42'],
        ]);

        $this->actingAs($hrUser)->post(route('attendance.import'), [
            'attendance_month' => '2026-03',
            'attendance_file' => $file,
        ])->assertRedirect();

        $import = AttendanceImport::firstOrFail();

        $this->assertSame(0, $import->imported_rows);
        $this->assertSame(1, $import->error_rows);
        $this->assertDatabaseHas('attendance_import_errors', [
            'attendance_import_id' => $import->id,
            'employee_code' => 'CA-E-11',
            'attendance_date' => '02/04/2026',
        ]);
        $this->assertDatabaseMissing('attendance_records', [
            'employee_id' => $employee->id,
        ]);
    }
}
