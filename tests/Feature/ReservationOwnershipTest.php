<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_free_reservation_and_gets_a_ticket_immediately(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/reservations', [
            'type' => 'site',
            'nombre' => 2,
            'id_site' => $site->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('reservation', [
            'id_user' => $user->id,
            'id_site' => $site->id,
            'statut' => 'confirmee',
        ]);
        $this->assertSame(2, Reservation::first()->tickets()->count());
    }

    public function test_owner_can_view_their_own_reservation(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/reservations/{$reservation->id}")
            ->assertOk();
    }

    public function test_another_user_cannot_view_someone_elses_reservation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $owner->id]);

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/reservations/{$reservation->id}")
            ->assertForbidden();
    }

    public function test_another_user_cannot_update_someone_elses_reservation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $owner->id, 'nombre' => 1]);

        $this->actingAs($intruder, 'sanctum')
            ->putJson("/api/reservations/{$reservation->id}", ['nombre' => 5])
            ->assertForbidden();

        $this->assertSame(1, $reservation->fresh()->nombre);
    }

    public function test_another_user_cannot_delete_someone_elses_reservation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $owner->id]);

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/reservations/{$reservation->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('reservation', ['id' => $reservation->id]);
    }

    public function test_index_only_returns_the_authenticated_users_own_reservations(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Reservation::factory()->count(2)->create(['id_user' => $user->id]);
        Reservation::factory()->count(3)->create(['id_user' => $other->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/reservations');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }
}
