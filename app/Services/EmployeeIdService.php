<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class EmployeeIdService
{
    public function currentCounter(): int
    {
        return (int) (Setting::where('key', 'employee_id_counter')->value('value') ?? 0);
    }

    public function employeeIdPrefix(): string
    {
        $prefix = trim((string) (Setting::where('key', 'employee_id_prefix')->value('value') ?? 'EMP'));

        return $prefix !== '' ? $prefix : 'EMP';
    }

    public function nextEmployeeIdPreview(): string
    {
        $prefix = $this->employeeIdPrefix();
        $counter = $this->currentCounter();

        do {
            $counter++;
            $employeeId = $this->formatEmployeeId($prefix, $counter);
        } while (Employee::where('employee_id', $employeeId)->exists());

        return $employeeId;
    }

    public function resetCounter(): void
    {
        Setting::updateOrCreate(
            ['key' => 'employee_id_counter'],
            ['value' => '0']
        );
    }

    public function generateNextEmployeeId(): string
    {
        return DB::transaction(function () {
            $prefix = $this->employeeIdPrefix();

            $counterSetting = Setting::firstOrCreate(
                ['key' => 'employee_id_counter'],
                ['value' => '0']
            );

            $counterSetting = Setting::whereKey($counterSetting->id)->lockForUpdate()->firstOrFail();
            $counter = (int) $counterSetting->value;

            do {
                $counter++;
                $employeeId = $this->formatEmployeeId($prefix, $counter);
            } while (Employee::where('employee_id', $employeeId)->exists());

            $counterSetting->update(['value' => (string) $counter]);

            return $employeeId;
        });
    }

    protected function formatEmployeeId(string $prefix, int $counter): string
    {
        return $prefix . str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
    }
}
