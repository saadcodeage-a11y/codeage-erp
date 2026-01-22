<?php

namespace Tests\Feature;

use Tests\TestCase;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\AdminUserSeeder;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the root URL redirects to the login page.
     */
    public function test_root_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /**
     * Test that the login page loads successfully.
     */
    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Welcome to CodeAge ERP');
        $response->assertSee('Sign in to continue to your dashboard');
    }

    /**
     * Test that user can login with valid credentials from seeder.
     */
    public function test_super_admin_can_login(): void
    {
        $this->seed(AdminUserSeeder::class);
        
        $response = $this->post('/login', [
            'email' => 'codeagepk@gmail.com',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /**
     * Test that authenticated user accessing root is redirected to dashboard.
     */
    public function test_authenticated_user_root_redirects_to_dashboard(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/dashboard');
    }

    /**
     * Test that authenticated user accessing login is redirected to dashboard.
     */
    public function test_authenticated_user_visiting_login_redirects_to_dashboard(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/dashboard');
    }
}
