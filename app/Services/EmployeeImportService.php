<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeImportService
{
    protected const REQUIRED_HEADERS = [
        'employee_id',
        'employee_name',
        'email',
        'contact_number',
        'cnic',
        'gender',
    ];

    public function __construct(
        protected EmployeeIdService $employeeIdService
    ) {
    }

    public function import(UploadedFile $file): array
    {
        $rows = $this->readCsvRows($file);

        if (count($rows) < 2) {
            return [
                'imported' => 0,
                'errors' => ['The uploaded CSV does not contain any employee rows.'],
                'counter' => $this->employeeIdService->currentCounter(),
            ];
        }

        [$headerRowIndex, $headerMap] = $this->locateHeaderRow($rows);
        $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, array_keys($headerMap)));

        if ($missingHeaders !== []) {
            return [
                'imported' => 0,
                'errors' => [
                    'The employee CSV is missing required columns: ' . implode(', ', $missingHeaders),
                ],
                'counter' => $this->employeeIdService->currentCounter(),
            ];
        }

        $defaultDepartment = Department::firstOrCreate(['name' => 'Unassigned']);
        $summary = [
            'imported' => 0,
            'errors' => [],
            'counter' => $this->employeeIdService->currentCounter(),
        ];

        DB::transaction(function () use ($rows, $headerRowIndex, $headerMap, $defaultDepartment, &$summary) {
            foreach (array_slice($rows, $headerRowIndex + 1) as $index => $row) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $rowNumber = $headerRowIndex + $index + 2;
                $payload = $this->extractPayload($row, $headerMap);

                $validator = Validator::make($payload, [
                    'employee_id' => 'required|string|max:255|unique:employees,employee_id',
                    'employee_name' => 'required|string|max:255',
                    'email' => 'required|email|max:255|unique:employees,email',
                    'contact_number' => 'nullable|string|max:255',
                    'cnic' => 'nullable|string|max:255',
                    'gender' => 'nullable|in:Male,Female,Other',
                ], [
                    'employee_id.unique' => 'Employee ID already exists in the ERP.',
                    'email.unique' => 'Email already exists in the ERP.',
                ]);

                if ($validator->fails()) {
                    $summary['errors'][] = "Row {$rowNumber}: " . $validator->errors()->first();
                    continue;
                }

                Employee::create([
                    'employee_id' => $payload['employee_id'],
                    'full_name' => $payload['employee_name'],
                    'email' => $payload['email'],
                    'phone' => $payload['contact_number'] ?: null,
                    'cnic' => $payload['cnic'] ?: null,
                    'gender' => $payload['gender'] ?: null,
                    'department_id' => $defaultDepartment->id,
                    'designation' => 'Not assigned',
                    'status' => 'active',
                ]);

                $summary['imported']++;
            }

            $summary['counter'] = $this->employeeIdService->syncCounterToHighestExisting();
        });

        return $summary;
    }

    protected function readCsvRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            return [];
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(function ($value) {
                $value = is_string($value) ? trim($value) : $value;

                return $value === '' ? null : $this->stripUtf8Bom((string) $value);
            }, $row);
        }

        fclose($handle);

        return $rows;
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

    protected function resolveHeaderMap(array $row): array
    {
        $map = [];

        foreach ($row as $index => $value) {
            $normalized = str($value ?? '')
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();

            $key = match ($normalized) {
                'employee_id', 'employee_code', 'id' => 'employee_id',
                'employee_name', 'name', 'full_name' => 'employee_name',
                'email', 'email_address' => 'email',
                'contact_number', 'contact', 'phone', 'phone_number', 'mobile', 'mobile_number' => 'contact_number',
                'cnic', 'nic' => 'cnic',
                'gender', 'sex' => 'gender',
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
            $value = isset($headerMap[$header]) ? ($row[$headerMap[$header]] ?? null) : null;
            $payload[$header] = $this->normalizeValue($header, $value);
        }

        return $payload;
    }

    protected function normalizeValue(string $field, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($this->stripUtf8Bom((string) $value));

        if ($normalized === '') {
            return null;
        }

        if ($field === 'gender') {
            return match (strtolower($normalized)) {
                'male', 'm' => 'Male',
                'female', 'f' => 'Female',
                'other', 'o' => 'Other',
                default => $normalized,
            };
        }

        return $normalized;
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function stripUtf8Bom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }
}
