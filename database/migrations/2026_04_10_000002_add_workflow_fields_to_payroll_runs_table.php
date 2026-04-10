<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('source_workbook');
            $table->foreignId('generated_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable()->after('generated_by');
            $table->timestamp('finalized_at')->nullable()->after('generated_at');
            $table->text('notes')->nullable()->after('finalized_at');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generated_by');
            $table->dropColumn(['status', 'generated_at', 'finalized_at', 'notes']);
        });
    }
};
