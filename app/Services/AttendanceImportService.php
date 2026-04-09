<?php

namespace App\Services;

use App\Models\AttendanceImport;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class AttendanceImportService
{
    protected const REQUIRED_HEADERS = [
        'employee_code',
        'name',
        'date',
        'clock_in',
        'clock_out',
        'late',
        'early',
        'absent',
        'work_time',
    ];

    public function import(UploadedFile $file, User $user, string $attendanceMonth): AttendanceImport
    {
        $attendanceImport = AttendanceImport::create([
            'imported_by_user_id' => $user->id,
            'source_file_name' => $file->getClientOriginalName(),
            'source_file_extension' => strtolower($file->getClientOriginalExtension()),
            'attendance_month' => $attendanceMonth,
            'imported_at' => now(),
        ]);

        try {
            $rows = $this->loadRows($file);
        } catch (Throwable) {
            $attendanceImport->errors()->create([
                'row_number' => 1,
                'reason' => 'The uploaded file format could not be read. Please upload the fingerprint machine export in the expected Excel format.',
            ]);

            $attendanceImport->update(['error_rows' => 1]);

            return $attendanceImport->fresh(['errors', 'importedBy']);
        }

        if ($rows === [] || count($rows) < 2) {
            $attendanceImport->errors()->create([
                'row_number' => 1,
                'reason' => 'The uploaded file does not contain any attendance rows.',
            ]);

            $attendanceImport->update(['error_rows' => 1]);

            return $attendanceImport->fresh(['errors', 'importedBy']);
        }

        [$headerRowIndex, $headerMap] = $this->locateHeaderRow($rows);

        $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, array_keys($headerMap)));
        if ($missingHeaders !== []) {
            $attendanceImport->errors()->create([
                'row_number' => $headerRowIndex + 1,
                'reason' => 'The uploaded file is missing required columns: ' . implode(', ', $missingHeaders),
                'row_payload' => $rows[$headerRowIndex] ?? [],
            ]);

            $attendanceImport->update(['error_rows' => 1]);

            return $attendanceImport->fresh(['errors', 'importedBy']);
        }

        $seenKeys = [];
        $summary = [
            'total_rows' => 0,
            'imported_rows' => 0,
            'error_rows' => 0,
            'duplicate_rows' => 0,
        ];

        DB::transaction(function () use ($rows, $headerRowIndex, $headerMap, $attendanceImport, $attendanceMonth, &$seenKeys, &$summary) {
            foreach (array_slice($rows, $headerRowIndex + 1) as $index => $row) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $summary['total_rows']++;
                $rowNumber = $headerRowIndex + $index + 2;
                $payload = $this->extractPayload($row, $headerMap);

                $employeeCode = $payload['employee_code'];
                $employeeName = $payload['name'];
                $attendanceDate = $payload['date'];

                if (! $employeeCode) {
                    $this->recordError($attendanceImport, $summary, $rowNumber, $payload, 'Employee ID column is required for every attendance row.');
                    continue;
                }

                $employee = Employee::where('employee_id', $employeeCode)->first();
                if (! $employee) {
                    $this->recordError($attendanceImport, $summary, $rowNumber, $payload, "Employee ID {$employeeCode} was not found in the ERP.");
                    continue;
                }

                $parsedDate = $this->parseDateValue($attendanceDate);
                if (! $parsedDate) {
                    $this->recordError($attendanceImport, $summary, $rowNumber, $payload, 'Attendance date could not be parsed.');
                    continue;
                }

                if ($parsedDate->format('Y-m') !== $attendanceMonth) {
                    $this->recordError(
                        $attendanceImport,
                        $summary,
                        $rowNumber,
                        $payload,
                        "This row belongs to {$parsedDate->format('F Y')}, but the selected import month is " . Carbon::createFromFormat('Y-m', $attendanceMonth)->format('F Y') . '.'
                    );
                    continue;
                }

                $recordKey = $employee->id . '|' . $parsedDate->toDateString();
                if (isset($seenKeys[$recordKey])) {
                    $this->recordError($attendanceImport, $summary, $rowNumber, $payload, 'This file contains a duplicate attendance row for the same employee and date.', true);
                    continue;
                }

                $seenKeys[$recordKey] = true;

                if (AttendanceRecord::where('employee_id', $employee->id)->whereDate('attendance_date', $parsedDate)->exists()) {
                    $this->recordError($attendanceImport, $summary, $rowNumber, $payload, 'Attendance for this employee and date already exists in the system.', true);
                    continue;
                }

                AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'attendance_import_id' => $attendanceImport->id,
                    'attendance_date' => $parsedDate->toDateString(),
                    'clock_in' => $this->parseTimeValue($payload['clock_in']),
                    'clock_out' => $this->parseTimeValue($payload['clock_out']),
                    'late_duration' => $this->parseDurationValue($payload['late']),
                    'early_duration' => $this->parseDurationValue($payload['early']),
                    'absent_duration' => $this->parseDurationValue($payload['absent']),
                    'work_duration' => $this->parseDurationValue($payload['work_time']),
                    'shift_start_time' => $this->parseTimeValue($employee->shift_start_time),
                    'shift_end_time' => $this->parseTimeValue($employee->shift_end_time),
                    'status' => $this->resolveStatus($payload),
                ]);

                $summary['imported_rows']++;
            }

            $attendanceImport->update($summary);
        });

        return $attendanceImport->fresh(['errors', 'importedBy']);
    }

    protected function loadRows(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());

            return $spreadsheet->getSheet(0)->toArray(null, false, false, false);
        } catch (Throwable) {
            return $this->parseLegacyWorksheetRows($file->getRealPath());
        }
    }

    protected function locateHeaderRow(array $rows): array
    {
        $bestIndex = 0;
        $bestMap = [];

        foreach ($rows as $index => $row) {
            $map = $this->resolveHeaderMap($row);

            if (count($map) > count($bestMap)) {
                $bestIndex = $index;
                $bestMap = $map;
            }

            if (array_diff(self::REQUIRED_HEADERS, array_keys($map)) === []) {
                return [$index, $map];
            }
        }

        return [$bestIndex, $bestMap];
    }

    protected function resolveHeaderMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $value) {
            $normalized = str($value ?? '')->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();

            $key = match ($normalized) {
                'no', 'no_' => 'employee_code',
                'employee_id', 'employee_code' => 'employee_code',
                'name' => 'name',
                'date' => 'date',
                'clock_in' => 'clock_in',
                'clock_out' => 'clock_out',
                'late' => 'late',
                'early' => 'early',
                'absent' => 'absent',
                'work_time' => 'work_time',
                default => null,
            };

            if ($key) {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    protected function extractPayload(array $row, array $headerMap): array
    {
        $payload = [];

        foreach (self::REQUIRED_HEADERS as $header) {
            $payload[$header] = isset($headerMap[$header]) ? trim((string) ($row[$headerMap[$header]] ?? '')) : null;
        }

        return $payload;
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function parseDateValue(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->startOfDay();
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim((string) $value))->startOfDay();
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseTimeValue(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('H:i:s');
        }

        foreach (['H:i:s', 'H:i', 'g:i A', 'g:iA'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim((string) $value))->format('H:i:s');
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseDurationValue(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('H:i');
        }

        $trimmed = trim((string) $value);

        if (preg_match('/^\d{1,2}:\d{2}$/', $trimmed)) {
            return str_pad(explode(':', $trimmed)[0], 2, '0', STR_PAD_LEFT) . ':' . explode(':', $trimmed)[1];
        }

        return null;
    }

    protected function parseLegacyWorksheetRows(string $path): array
    {
        $binary = file_get_contents($path);

        if ($binary === false || strlen($binary) < 4) {
            throw new \RuntimeException('Attendance file could not be read.');
        }

        $cells = [];
        $offset = 0;
        $length = strlen($binary);

        while ($offset + 4 <= $length) {
            $recordId = unpack('v', substr($binary, $offset, 2))[1];
            $recordLength = unpack('v', substr($binary, $offset + 2, 2))[1];
            $payload = substr($binary, $offset + 4, $recordLength);

            if (strlen($payload) !== $recordLength) {
                break;
            }

            if ($recordId === 0x0004 && $recordLength >= 8) {
                $row = unpack('v', substr($payload, 0, 2))[1];
                $column = unpack('v', substr($payload, 2, 2))[1];
                $textLength = ord($payload[7]);
                $value = substr($payload, 8, $textLength);

                $cells[$row][$column] = $this->normalizeLegacyCellValue($value);
            }

            $offset += 4 + $recordLength;
        }

        if ($cells === []) {
            throw new \RuntimeException('Attendance file did not contain readable worksheet rows.');
        }

        ksort($cells);
        $maxColumn = max(array_map(static fn (array $row): int => max(array_keys($row)), $cells));
        $rows = [];

        foreach ($cells as $columns) {
            ksort($columns);
            $row = [];

            for ($column = 0; $column <= $maxColumn; $column++) {
                $row[$column] = $columns[$column] ?? null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    protected function normalizeLegacyCellValue(string $value): string
    {
        $normalized = str_replace("\0", '', $value);

        return trim(mb_convert_encoding($normalized, 'UTF-8', 'Windows-1252'));
    }

    protected function resolveStatus(array $payload): string
    {
        $clockIn = $this->parseTimeValue($payload['clock_in']);
        $clockOut = $this->parseTimeValue($payload['clock_out']);
        $late = $this->parseDurationValue($payload['late']);
        $early = $this->parseDurationValue($payload['early']);
        $absent = $this->parseDurationValue($payload['absent']);
        $work = $this->parseDurationValue($payload['work_time']);

        if ($absent && $absent !== '00:00') {
            return 'absent';
        }

        if (($clockIn && ! $clockOut) || (! $clockIn && $clockOut)) {
            return 'incomplete';
        }

        if ($late && $late !== '00:00') {
            return 'late';
        }

        if ($early && $early !== '00:00') {
            return 'early_leave';
        }

        if (! $clockIn && ! $clockOut && ! $work) {
            return 'absent';
        }

        return 'present';
    }

    protected function recordError(
        AttendanceImport $attendanceImport,
        array &$summary,
        int $rowNumber,
        array $payload,
        string $reason,
        bool $isDuplicate = false
    ): void {
        $attendanceImport->errors()->create([
            'row_number' => $rowNumber,
            'employee_code' => $payload['employee_code'] ?? null,
            'employee_name' => $payload['name'] ?? null,
            'attendance_date' => $payload['date'] ?? null,
            'reason' => $reason,
            'row_payload' => $payload,
        ]);

        $summary['error_rows']++;

        if ($isDuplicate) {
            $summary['duplicate_rows']++;
        }
    }
}
