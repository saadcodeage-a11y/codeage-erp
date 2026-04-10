<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payroll_adjustments', function (Blueprint $table) {
            $table->decimal('security_deduction', 12, 2)->nullable()->after('punctuality_bonus');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payroll_adjustments', function (Blueprint $table) {
            $table->dropColumn('security_deduction');
        });
    }
};
