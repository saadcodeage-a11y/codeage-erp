<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_shows_tax_formula_tab(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
        ]);

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Tax Formulas')
            ->assertSee('Taxable Income Formula');
    }

    public function test_can_save_tax_formula_rules(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
        ]);

        $response = $this->actingAs($user)->postJson(route('settings.tax-formulas.update'), [
            'taxable_income_formula' => '(basic_salary + last_increment) * 0.5',
            'slabs' => [
                [
                    'label' => 'Flat Rule',
                    'min' => 0,
                    'max' => null,
                    'formula' => 'taxable_income + 125',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSame(
            '(basic_salary + last_increment) * 0.5',
            Setting::query()->where('key', 'tax_taxable_income_formula')->value('value')
        );

        $this->assertStringContainsString(
            'taxable_income + 125',
            (string) Setting::query()->where('key', 'tax_slab_rules')->value('value')
        );
    }

    public function test_can_load_tax_formula_example_for_employee(): void
    {
        $user = User::factory()->create([
            'role' => 'Super Admin',
        ]);

        $department = Department::create(['name' => 'Finance']);
        $employee = Employee::create([
            'full_name' => 'Formula Example Employee',
            'email' => 'formula-example@example.com',
            'status' => 'active',
            'department_id' => $department->id,
            'designation' => 'Analyst',
            'employee_id' => 'CA-E-777',
            'current_salary' => 75000,
            'last_increment' => 5000,
        ]);

        $response = $this->actingAs($user)->getJson(route('settings.tax-formulas.example', [
            'employee_id' => $employee->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('employee.employee_id', 'CA-E-777')
            ->assertJsonFragment([
                'label' => 'basic_salary',
                'value' => 75000,
            ]);
    }
}
