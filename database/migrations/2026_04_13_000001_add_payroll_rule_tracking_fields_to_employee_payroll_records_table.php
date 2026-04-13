<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payroll_records', function (Blueprint $table) {
            $table->unsignedInteger('late_count')->default(0)->after('days_absent');
            $table->unsignedInteger('late_absent_equivalent')->default(0)->after('late_count');
            $table->unsignedInteger('unpaid_leave_days')->default(0)->after('late_absent_equivalent');
            $table->decimal('annual_tax_total', 12, 2)->default(0)->after('income_tax');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payroll_records', function (Blueprint $table) {
            $table->dropColumn([
                'late_count',
                'late_absent_equivalent',
                'unpaid_leave_days',
                'annual_tax_total',
            ]);
        });
    }
};
