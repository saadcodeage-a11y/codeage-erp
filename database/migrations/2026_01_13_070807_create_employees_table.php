<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('employee_id')->nullable()->unique(); // EMP001
            $table->string('designation')->nullable();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->enum('status', ['active', 'inactive', 'invited', 'pending_approval', 'on_leave', 'terminated'])->default('active');
            $table->date('hiring_date')->nullable(); // Renamed from joined_at effectively
            
            // Personal Info
            $table->string('cnic')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender')->nullable(); // Select: Male, Female, Other
            $table->date('dob')->nullable();
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('father_name')->nullable();
            $table->string('guardian_contact')->nullable();
            
            // Education
            $table->string('education_level')->nullable();
            $table->string('field_of_study')->nullable();
            
            // Job Info
            $table->string('job_location')->nullable();
            $table->string('payroll_status')->nullable();
            $table->text('hr_comments')->nullable();

            // Documents
            $table->string('profile_picture')->nullable();
            $table->string('cnic_front_path')->nullable();
            $table->string('cnic_back_path')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('transcript_path')->nullable();

            // Bank Info
            $table->string('bank_account_title')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('iban')->nullable();
            $table->text('banking_comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
