<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendance_imports', 'attendance_month')) {
            Schema::table('attendance_imports', function (Blueprint $table) {
                $table->string('attendance_month', 7)->after('source_file_extension');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendance_imports', 'attendance_month')) {
            Schema::table('attendance_imports', function (Blueprint $table) {
                $table->dropColumn('attendance_month');
            });
        }
    }
};
