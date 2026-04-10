<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('adjustment_month');
            $table->decimal('incentives_bonus', 12, 2)->default(0);
            $table->decimal('punctuality_bonus', 12, 2)->default(0);
            $table->decimal('attendance_penalty', 12, 2)->default(0);
            $table->decimal('arrears_adjustment', 12, 2)->default(0);
            $table->decimal('other_adjustment', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'adjustment_month'], 'employee_payroll_adjustments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_adjustments');
    }
};
