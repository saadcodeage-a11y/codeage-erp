<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TaxFormulaService
{
    protected const TAXABLE_FORMULA_KEY = 'tax_taxable_income_formula';
    protected const TAX_SLABS_KEY = 'tax_slab_rules';

    public function __construct(
        protected MathExpressionEvaluator $expressionEvaluator
    ) {
    }

    public function availableVariables(): array
    {
        return [
            'basic_salary' => 'Employee base salary',
            'last_increment' => 'Latest increment value',
            'incentives_bonus' => 'Manual incentives or bonus',
            'punctuality_bonus' => 'Punctuality bonus amount',
            'positive_arrears' => 'Positive arrears adjustment',
            'positive_other' => 'Other positive adjustment',
            'security_deduction' => 'Monthly security deduction',
            'non_paid_leave_deduction' => 'Calculated unpaid leave deduction',
            'attendance_penalty' => 'Manual attendance penalty',
            'arrears_deduction' => 'Negative arrears deduction',
            'other_deduction' => 'Other manual deduction',
            'earnings_subtotal' => 'Total positive earnings before deductions',
            'daily_rate' => 'Resolved daily rate for leave deduction',
            'gross_salary' => 'Gross salary after deductions and before tax',
            'days_absent' => 'Actual absent days in payout month',
            'late_count' => 'Late arrivals in payout month',
            'late_absent_equivalent' => 'Derived absent count from late arrivals',
            'unpaid_leave_days' => 'Total unpaid leave days',
            'short_hours_days' => 'Short-hour attendance days',
            'security_total_deducted' => 'Running security total after this month',
            'taxable_income' => 'Resolved taxable income from the main tax formula',
            'slab_min' => 'Current slab minimum bound',
            'slab_max' => 'Current slab maximum bound',
        ];
    }

    public function configuration(): array
    {
        $taxableFormula = trim((string) Setting::query()->where('key', self::TAXABLE_FORMULA_KEY)->value('value'));
        $slabJson = Setting::query()->where('key', self::TAX_SLABS_KEY)->value('value');
        $slabs = json_decode((string) $slabJson, true);

        $config = [
            'taxable_income_formula' => $taxableFormula !== '' ? $taxableFormula : $this->defaultConfiguration()['taxable_income_formula'],
            'slabs' => is_array($slabs) && ! empty($slabs) ? $this->normalizeSlabs($slabs) : $this->defaultConfiguration()['slabs'],
        ];

        try {
            $this->validateConfiguration($config);
        } catch (ValidationException) {
            return $this->defaultConfiguration();
        }

        return $config;
    }

    public function defaultConfiguration(): array
    {
        return [
            'taxable_income_formula' => 'gross_salary',
            'slabs' => [
                [
                    'label' => 'Up to 50,000',
                    'min' => 0,
                    'max' => 50000,
                    'formula' => '0',
                ],
                [
                    'label' => '50,000.01 to 100,000',
                    'min' => 50000.01,
                    'max' => 100000,
                    'formula' => '(taxable_income - 50000) * 0.01',
                ],
                [
                    'label' => 'Above 100,000',
                    'min' => 100000.01,
                    'max' => null,
                    'formula' => '(taxable_income - 50000) * 0.11 + 6000',
                ],
            ],
        ];
    }

    public function saveConfiguration(array $config): array
    {
        $validated = $this->validateConfiguration($config);

        Setting::updateOrCreate(
            ['key' => self::TAXABLE_FORMULA_KEY],
            ['value' => $validated['taxable_income_formula']]
        );

        Setting::updateOrCreate(
            ['key' => self::TAX_SLABS_KEY],
            ['value' => json_encode($validated['slabs'], JSON_UNESCAPED_UNICODE)]
        );

        return $validated;
    }

    public function validateConfiguration(array $config): array
    {
        $formula = trim((string) ($config['taxable_income_formula'] ?? ''));
        $slabs = $this->normalizeSlabs($config['slabs'] ?? []);

        $errors = [];

        if ($formula === '') {
            $errors['taxable_income_formula'] = 'Taxable income formula is required.';
        }

        if (empty($slabs)) {
            $errors['slabs'] = 'At least one tax slab is required.';
        }

        $sampleVariables = $this->sampleVariables();

        try {
            $taxableIncome = $this->expressionEvaluator->evaluate($formula, $sampleVariables);
        } catch (\Throwable $exception) {
            $errors['taxable_income_formula'] = $exception->getMessage();
            $taxableIncome = 0.0;
        }

        foreach ($slabs as $index => $slab) {
            if ($slab['formula'] === '') {
                $errors["slabs.$index.formula"] = 'Tax formula is required for each slab.';
                continue;
            }

            if ($slab['max'] !== null && $slab['min'] > $slab['max']) {
                $errors["slabs.$index.max"] = 'Maximum amount must be greater than or equal to minimum amount.';
            }

            try {
                $this->expressionEvaluator->evaluate($slab['formula'], array_merge($sampleVariables, [
                    'taxable_income' => $taxableIncome,
                    'slab_min' => (float) $slab['min'],
                    'slab_max' => (float) ($slab['max'] ?? $taxableIncome),
                ]));
            } catch (\Throwable $exception) {
                $errors["slabs.$index.formula"] = $exception->getMessage();
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'taxable_income_formula' => $formula,
            'slabs' => array_values($slabs),
        ];
    }

    public function calculate(array $variables): array
    {
        $config = $this->configuration();
        $taxableIncome = max(round($this->expressionEvaluator->evaluate($config['taxable_income_formula'], $variables), 2), 0.0);
        $slab = $this->resolveSlab($config['slabs'], $taxableIncome);

        $tax = max(round($this->expressionEvaluator->evaluate($slab['formula'], array_merge($variables, [
            'taxable_income' => $taxableIncome,
            'slab_min' => (float) $slab['min'],
            'slab_max' => (float) ($slab['max'] ?? $taxableIncome),
        ])), 2), 0.0);

        return [
            'taxable_income' => $taxableIncome,
            'income_tax' => $tax,
            'slab' => $slab,
            'configuration' => $config,
        ];
    }

    protected function resolveSlab(array $slabs, float $taxableIncome): array
    {
        foreach ($slabs as $slab) {
            $min = (float) $slab['min'];
            $max = $slab['max'] !== null ? (float) $slab['max'] : null;

            if ($taxableIncome < $min) {
                continue;
            }

            if ($max !== null && $taxableIncome > $max) {
                continue;
            }

            return $slab;
        }

        return collect($slabs)->sortBy('min')->last() ?: [
            'label' => 'Default',
            'min' => 0,
            'max' => null,
            'formula' => '0',
        ];
    }

    protected function normalizeSlabs(array $slabs): array
    {
        return collect($slabs)
            ->map(function ($slab, $index) {
                return [
                    'label' => trim((string) ($slab['label'] ?? ('Slab ' . ($index + 1)))),
                    'min' => round((float) ($slab['min'] ?? 0), 2),
                    'max' => $slab['max'] === '' || $slab['max'] === null ? null : round((float) $slab['max'], 2),
                    'formula' => trim((string) ($slab['formula'] ?? '')),
                ];
            })
            ->sortBy('min')
            ->values()
            ->all();
    }

    protected function sampleVariables(): array
    {
        return array_fill_keys(array_keys($this->availableVariables()), 100.0);
    }
}
