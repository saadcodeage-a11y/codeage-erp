<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'payment_mode')) {
                $table->string('payment_mode')->nullable()->after('payroll_status');
            }

            if (! Schema::hasColumn('employees', 'bank_code')) {
                $table->string('bank_code', 20)->nullable()->after('bank_name');
            }

            if (! Schema::hasColumn('employees', 'current_salary')) {
                $table->decimal('current_salary', 12, 2)->nullable()->after('iban');
            }

            if (! Schema::hasColumn('employees', 'last_increment')) {
                $table->decimal('last_increment', 12, 2)->nullable()->after('current_salary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('employees', 'payment_mode') ? 'payment_mode' : null,
                Schema::hasColumn('employees', 'bank_code') ? 'bank_code' : null,
                Schema::hasColumn('employees', 'current_salary') ? 'current_salary' : null,
                Schema::hasColumn('employees', 'last_increment') ? 'last_increment' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
