<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $timestamp = now();

        $defaultRoles = collect([
            'Super Admin',
            'HR Manager',
            'Accounts Manager',
            'Employee',
        ])->map(fn (string $name) => [
            'name' => $name,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::table('roles')->insert($defaultRoles->all());

        $userRoles = DB::table('users')
            ->whereNotNull('role')
            ->distinct()
            ->pluck('role')
            ->filter()
            ->diff($defaultRoles->pluck('name'))
            ->values()
            ->map(fn (string $name) => [
                'name' => $name,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

        if ($userRoles->isNotEmpty()) {
            DB::table('roles')->insert($userRoles->all());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
