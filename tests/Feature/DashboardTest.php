<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use Database\Seeders\DashboardDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test dashboard loads with correct data.
     */
    public function test_dashboard_displays_seeded_data(): void
    {
        // 1. Authenticate
        $user = User::factory()->create();
        
        // 2. Seed Data
        $this->seed(DashboardDataSeeder::class);

        // 3. Visit Dashboard
        $response = $this->actingAs($user)->get('/dashboard');

        // 4. Assert
        $response->assertStatus(200);
        $response->assertSee('Total Employees');
        $response->assertSee('8'); // Total seeded employees
        $response->assertSee('Rajesh Kumar joined as Software Engineer'); // Activity Log
        $response->assertSee('Engineering'); // Department
    }
}
