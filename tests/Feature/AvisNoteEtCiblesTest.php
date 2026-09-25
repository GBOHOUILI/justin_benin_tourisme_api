<?php

namespace Tests\Feature;

use App\Models\Avis;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Note chiffrée par service" (2026-09-25) : note (1-5) sur tout avis, et
 * extension du système d'avis à Hôtel/Restaurant/Transport qui n'ont aucune
 * Reservation (contrairement à Site/Événement) - un avis s'y rattache donc
 * directement (id_user + id_hotel/id_restaurant/id_transport), sans preuve
 * de visite possible. Couvre ce nouveau chemin ; AvisOwnershipTest couvre
 * déjà le chemin existant via réservation (désormais avec note obligatoire).
 */
class AvisNoteEtCiblesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_avis_directly_on_a_hotel(): void
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create(['status' => 'valide']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_hotel' => $hotel->id,
            'message' => 'Séjour très agréable.',
            'note' => 4,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('avis', [
            'id_hotel' => $hotel->id,
            'id_user' => $user->id,
            'note' => 4,
            'status' => 'en_attente',
        ]);
    }

    public function test_cannot_create_a_second_avis_on_the_same_hotel(): void
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create(['status' => 'valide']);
        Avis::factory()->create(['id_reservation' => null, 'id_hotel' => $hotel->id, 'id_user' => $user->id, 'note' => 5]);

        $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_hotel' => $hotel->id,
            'message' => 'Deuxième avis',
            'note' => 3,
        ])->assertUnprocessable();
    }

    public function test_avis_requires_exactly_one_target(): void
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create(['status' => 'valide']);
        $reservation = Reservation::factory()->create(['id_user' => $user->id, 'statut' => 'confirmee']);

        // Aucune cible
        $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'message' => 'Sans cible',
            'note' => 3,
        ])->assertUnprocessable();

        // Deux cibles à la fois
        $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_hotel' => $hotel->id,
            'id_reservation' => $reservation->id,
            'message' => 'Deux cibles',
            'note' => 3,
        ])->assertUnprocessable();
    }

    public function test_note_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create(['status' => 'valide']);

        $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_hotel' => $hotel->id,
            'message' => 'Note invalide',
            'note' => 6,
        ])->assertUnprocessable();
    }

    public function test_another_user_cannot_update_or_delete_someone_elses_direct_avis(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $hotel = Hotel::factory()->create(['status' => 'valide']);
        $avis = Avis::factory()->create(['id_reservation' => null, 'id_hotel' => $hotel->id, 'id_user' => $owner->id, 'note' => 5]);

        $this->actingAs($intruder, 'sanctum')
            ->putJson("/api/avis/{$avis->id}", ['message' => 'Modifié par un intrus'])
            ->assertForbidden();

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/avis/{$avis->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('avis', ['id' => $avis->id]);
    }

    public function test_site_exposes_note_moyenne_and_nombre_avis_counting_only_approved(): void
    {
        $site = Site::factory()->create(['status' => 'valide']);

        $r1 = Reservation::factory()->create(['id_site' => $site->id, 'statut' => 'confirmee']);
        $r2 = Reservation::factory()->create(['id_site' => $site->id, 'statut' => 'confirmee']);
        $r3 = Reservation::factory()->create(['id_site' => $site->id, 'statut' => 'confirmee']);
        Avis::factory()->create(['id_reservation' => $r1->id, 'note' => 4, 'status' => 'approuve']);
        Avis::factory()->create(['id_reservation' => $r2->id, 'note' => 2, 'status' => 'approuve']);
        Avis::factory()->create(['id_reservation' => $r3->id, 'note' => 1, 'status' => 'en_attente']); // pas encore modéré, ne doit pas compter

        $response = $this->getJson("/api/sites/{$site->id}");

        $response->assertOk()->assertJsonPath('nombre_avis', 2);
        $this->assertEquals(3.0, $response->json('note_moyenne'));
    }

    public function test_hotel_exposes_note_moyenne_from_direct_avis(): void
    {
        $hotel = Hotel::factory()->create(['status' => 'valide']);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        Avis::factory()->create(['id_reservation' => null, 'id_hotel' => $hotel->id, 'id_user' => $u1->id, 'note' => 5, 'status' => 'approuve']);
        Avis::factory()->create(['id_reservation' => null, 'id_hotel' => $hotel->id, 'id_user' => $u2->id, 'note' => 3, 'status' => 'approuve']);

        $response = $this->getJson("/api/hotels/{$hotel->id}");

        $response->assertOk()->assertJsonPath('nombre_avis', 2);
        $this->assertEquals(4.0, $response->json('note_moyenne'));
    }
}
