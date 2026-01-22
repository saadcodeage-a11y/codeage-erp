<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('transcript_path');
            $table->string('onboarding_token')->nullable()->unique()->after('signature_path');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_token');
            $table->timestamp('policy_accepted_at')->nullable()->after('onboarding_completed_at');
            $table->foreignId('bank_id')->nullable()->after('iban')->constrained('banks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropColumn(['signature_path', 'onboarding_token', 'onboarding_completed_at', 'policy_accepted_at', 'bank_id']);
        });
    }
};
