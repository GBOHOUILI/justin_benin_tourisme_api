<?php

namespace Tests\Feature\Auth;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'nom' => 'Sena',
            'prenom' => 'Justin',
            'tel' => '+22997000000',
            'email' => 'justin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'user', 'token', 'type']);

        $this->assertDatabaseHas('users', ['email' => 'justin@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existe@example.com']);

        $response = $this->postJson('/api/register', [
            'nom' => 'Sena',
            'prenom' => 'Justin',
            'tel' => '+22997000000',
            'email' => 'existe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_succeeds_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_admin_login_succeeds_with_correct_credentials(): void
    {
        $admin = Admin::factory()->create([
            'tel' => '+22901000000',
            'password' => Hash::make('admin123'),
            'status' => true,
        ]);

        $response = $this->postJson('/api/admin/login', [
            'tel' => $admin->tel,
            'password' => 'admin123',
        ]);

        $response->assertOk()->assertJsonStructure(['admin', 'token']);
    }

    public function test_admin_login_rejects_disabled_account(): void
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('admin123'),
            'status' => false,
        ]);

        $response = $this->postJson('/api/admin/login', [
            'tel' => $admin->tel,
            'password' => 'admin123',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('tel');
    }

    public function test_admin_login_rejects_wrong_credentials(): void
    {
        Admin::factory()->create(['password' => Hash::make('admin123')]);

        $response = $this->postJson('/api/admin/login', [
            'tel' => '+22900000000',
            'password' => 'wrong',
        ]);

        $response->assertUnprocessable();
    }
}
