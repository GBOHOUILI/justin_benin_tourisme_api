<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Régression sur le fix IDOR UserController::show/update/destroy
 * (cf. ROADMAP.md, commit 8660fd1).
 */
class UserOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_own_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/users/{$user->id}")
            ->assertOk();
    }

    public function test_user_cannot_view_someone_elses_profile(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/users/{$other->id}")
            ->assertForbidden();
    }

    public function test_user_cannot_update_someone_elses_profile(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['nom' => 'Original']);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/users/{$other->id}", ['nom' => 'Usurpé'])
            ->assertForbidden();

        $this->assertSame('Original', $other->fresh()->nom);
    }

    public function test_user_cannot_delete_someone_elses_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/users/{$other->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_user_can_delete_their_own_account_via_sanctum_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/users/{$user->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_delete_any_user_via_admin_route(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'admin')
            ->deleteJson("/api/admin/users/{$user->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
