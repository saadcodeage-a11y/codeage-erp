<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('evaluation_type', ['monthly', 'biannual']);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('manager_draft');

            $table->unsignedTinyInteger('manager_performance')->nullable();
            $table->unsignedTinyInteger('manager_punctuality')->nullable();
            $table->unsignedTinyInteger('manager_behaviour')->nullable();
            $table->unsignedTinyInteger('manager_learning')->nullable();
            $table->unsignedTinyInteger('manager_participation')->nullable();
            $table->text('manager_feedback')->nullable();
            $table->timestamp('manager_submitted_at')->nullable();

            $table->unsignedTinyInteger('hr_performance')->nullable();
            $table->unsignedTinyInteger('hr_punctuality')->nullable();
            $table->unsignedTinyInteger('hr_behaviour')->nullable();
            $table->unsignedTinyInteger('hr_learning')->nullable();
            $table->unsignedTinyInteger('hr_participation')->nullable();
            $table->text('hr_feedback')->nullable();
            $table->foreignId('hr_finalized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_finalized_at')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'evaluation_type', 'period_start', 'period_end'], 'performance_eval_unique_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_evaluations');
    }
};
