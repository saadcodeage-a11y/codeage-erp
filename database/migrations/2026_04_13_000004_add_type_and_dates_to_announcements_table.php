<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('announcement_type')->default('general')->after('message');
            $table->string('date_mode')->nullable()->after('announcement_type');
            $table->date('event_date')->nullable()->after('date_mode');
            $table->date('event_start_date')->nullable()->after('event_date');
            $table->date('event_end_date')->nullable()->after('event_start_date');
        });

        DB::table('announcements')
            ->whereNull('announcement_type')
            ->update(['announcement_type' => 'general']);
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn([
                'announcement_type',
                'date_mode',
                'event_date',
                'event_start_date',
                'event_end_date',
            ]);
        });
    }
};
