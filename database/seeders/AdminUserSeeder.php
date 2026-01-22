<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if user exists to avoid duplicates if run multiple times
        if (!User::where('email', 'codeagepk@gmail.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'codeagepk@gmail.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]);
            $this->command->info('Super Admin user created successfully.');
        } else {
            $this->command->info('Super Admin user already exists.');
        }
    }
}
