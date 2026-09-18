<?php

namespace Tests\Feature;

use App\Models\Avis;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvisOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_an_avis_on_their_own_confirmed_reservation(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $user->id, 'statut' => 'confirmee']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_reservation' => $reservation->id,
            'message' => "Très belle visite.",
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('avis', [
            'id_reservation' => $reservation->id,
            'status' => 'en_attente',
        ]);
    }

    public function test_cannot_create_an_avis_on_a_reservation_not_yet_confirmed(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $user->id, 'statut' => 'en_attente_paiement']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_reservation' => $reservation->id,
            'message' => 'Avis prématuré',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('avis', ['id_reservation' => $reservation->id]);
    }

    public function test_a_tourist_cannot_self_approve_an_avis_via_store(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $user->id, 'statut' => 'confirmee']);

        $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_reservation' => $reservation->id,
            'message' => 'Parfait',
            'status' => 'approuve',
        ]);

        $this->assertDatabaseHas('avis', [
            'id_reservation' => $reservation->id,
            'status' => 'en_attente',
        ]);
    }

    public function test_user_cannot_create_an_avis_on_someone_elses_reservation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $owner->id, 'statut' => 'confirmee']);

        $this->actingAs($intruder, 'sanctum')->postJson('/api/avis', [
            'id_reservation' => $reservation->id,
            'message' => "Je m'incruste",
        ])->assertForbidden();

        $this->assertDatabaseMissing('avis', ['id_reservation' => $reservation->id]);
    }

    public function test_cannot_create_a_second_avis_on_the_same_reservation(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $user->id, 'statut' => 'confirmee']);
        Avis::factory()->create(['id_reservation' => $reservation->id]);

        $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_reservation' => $reservation->id,
            'message' => 'Deuxième avis',
        ])->assertUnprocessable();
    }

    public function test_another_user_cannot_update_someone_elses_avis(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $owner->id, 'statut' => 'confirmee']);
        $avis = Avis::factory()->create(['id_reservation' => $reservation->id, 'message' => 'Original']);

        $this->actingAs($intruder, 'sanctum')
            ->putJson("/api/avis/{$avis->id}", ['message' => 'Modifié par un intrus'])
            ->assertForbidden();

        $this->assertSame('Original', $avis->fresh()->message);
    }

    public function test_another_user_cannot_delete_someone_elses_avis(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $owner->id, 'statut' => 'confirmee']);
        $avis = Avis::factory()->create(['id_reservation' => $reservation->id]);

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/avis/{$avis->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('avis', ['id' => $avis->id]);
    }

    public function test_owner_can_update_and_delete_their_own_avis(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $user->id, 'statut' => 'confirmee']);
        $avis = Avis::factory()->create(['id_reservation' => $reservation->id]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/avis/{$avis->id}", ['message' => 'Édité par le propriétaire'])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/avis/{$avis->id}")
            ->assertOk();

        $this->assertDatabaseMissing('avis', ['id' => $avis->id]);
    }

    public function test_index_can_filter_avis_by_site(): void
    {
        $user = User::factory()->create();
        $site = \App\Models\Site::factory()->create();
        $otherSite = \App\Models\Site::factory()->create();
        $reservation = Reservation::factory()->create(['id_user' => $user->id, 'id_site' => $site->id, 'statut' => 'confirmee']);
        $otherReservation = Reservation::factory()->create(['id_user' => $user->id, 'id_site' => $otherSite->id, 'statut' => 'confirmee']);
        Avis::factory()->create(['id_reservation' => $reservation->id]);
        Avis::factory()->create(['id_reservation' => $otherReservation->id]);

        $response = $this->getJson("/api/avis?id_site={$site->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
