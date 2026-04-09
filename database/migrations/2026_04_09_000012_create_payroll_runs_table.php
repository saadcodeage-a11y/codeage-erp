<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('pay_period_month');
            $table->date('payment_date')->nullable();
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->string('source_workbook')->nullable();
            $table->timestamps();

            $table->unique('pay_period_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
