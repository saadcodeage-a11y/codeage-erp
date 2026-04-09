<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_security_fund_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('fiscal_year_label', 20);
            $table->date('snapshot_month');
            $table->decimal('opening_arrears', 12, 2)->default(0);
            $table->decimal('july_amount', 12, 2)->default(0);
            $table->decimal('august_amount', 12, 2)->default(0);
            $table->decimal('september_amount', 12, 2)->default(0);
            $table->decimal('october_amount', 12, 2)->default(0);
            $table->decimal('november_amount', 12, 2)->default(0);
            $table->decimal('december_amount', 12, 2)->default(0);
            $table->decimal('january_amount', 12, 2)->default(0);
            $table->decimal('february_amount', 12, 2)->default(0);
            $table->decimal('march_amount', 12, 2)->default(0);
            $table->decimal('april_amount', 12, 2)->default(0);
            $table->decimal('may_amount', 12, 2)->default(0);
            $table->decimal('june_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_in_account', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'fiscal_year_label', 'snapshot_month'], 'security_snapshot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_security_fund_snapshots');
    }
};
