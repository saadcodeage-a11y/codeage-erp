<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('bank_code', 20)->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('beneficiary_account_no')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email_address')->nullable();
            $table->unsignedInteger('days_absent')->default(0);
            $table->unsignedInteger('short_hours_days')->default(0);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('last_increment', 12, 2)->default(0);
            $table->decimal('incentives_bonus', 12, 2)->default(0);
            $table->decimal('punctuality_bonus', 12, 2)->default(0);
            $table->decimal('positive_arrears', 12, 2)->default(0);
            $table->decimal('positive_other', 12, 2)->default(0);
            $table->decimal('security_deduction', 12, 2)->default(0);
            $table->decimal('non_paid_leave_deduction', 12, 2)->default(0);
            $table->decimal('attendance_penalty', 12, 2)->default(0);
            $table->decimal('arrears_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('income_tax', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_records');
    }
};
