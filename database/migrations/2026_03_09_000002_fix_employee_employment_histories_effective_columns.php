<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE employee_employment_histories
            MODIFY effective_from DATETIME NOT NULL,
            MODIFY effective_to DATETIME NULL DEFAULT NULL
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE employee_employment_histories
            MODIFY effective_from TIMESTAMP NOT NULL,
            MODIFY effective_to TIMESTAMP NULL DEFAULT NULL
        ");
    }
};
