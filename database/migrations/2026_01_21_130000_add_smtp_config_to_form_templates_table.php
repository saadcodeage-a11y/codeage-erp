<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->foreignId('smtp_config_id')->nullable()->after('is_active')->constrained('smtp_configurations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropForeign(['smtp_config_id']);
            $table->dropColumn('smtp_config_id');
        });
    }
};
