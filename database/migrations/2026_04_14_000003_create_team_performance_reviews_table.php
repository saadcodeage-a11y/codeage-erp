<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('review_month');
            $table->unsignedTinyInteger('rating');
            $table->text('feedback');
            $table->timestamps();

            $table->unique(['employee_id', 'manager_user_id', 'review_month'], 'team_reviews_unique_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_performance_reviews');
    }
};
