<?php

namespace Tests\Feature;

use App\Models\Avis;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Utilisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvisOwnershipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Construit la chaîne réelle user -> reservation -> ticket -> utilisation,
     * seule voie valide pour rattacher un avis (cf. AvisController::store).
     */
    private function utilisationFor(User $user): Utilisation
    {
        $reservation = Reservation::factory()->create(['id_user' => $user->id]);
        $ticket = Ticket::factory()->create(['id_reservation' => $reservation->id]);
        return Utilisation::factory()->create(['id_ticket' => $ticket->id]);
    }

    public function test_owner_can_create_an_avis_on_their_own_utilisation(): void
    {
        $user = User::factory()->create();
        $utilisation = $this->utilisationFor($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_utilisation' => $utilisation->id,
            'message' => "Très belle visite.",
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('avis', [
            'id_utilisation' => $utilisation->id,
            'status' => 'en_attente',
        ]);
    }

    public function test_a_tourist_cannot_self_approve_an_avis_via_store(): void
    {
        $user = User::factory()->create();
        $utilisation = $this->utilisationFor($user);

        $this->actingAs($user, 'sanctum')->postJson('/api/avis', [
            'id_utilisation' => $utilisation->id,
            'message' => 'Parfait',
            'status' => 'approuve',
        ]);

        $this->assertDatabaseHas('avis', [
            'id_utilisation' => $utilisation->id,
            'status' => 'en_attente',
        ]);
    }

    public function test_user_cannot_create_an_avis_on_someone_elses_utilisation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $utilisation = $this->utilisationFor($owner);

        $this->actingAs($intruder, 'sanctum')->postJson('/api/avis', [
            'id_utilisation' => $utilisation->id,
            'message' => "Je m'incruste",
        ])->assertForbidden();

        $this->assertDatabaseMissing('avis', ['id_utilisation' => $utilisation->id]);
    }

    public function test_another_user_cannot_update_someone_elses_avis(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $utilisation = $this->utilisationFor($owner);
        $avis = Avis::factory()->create(['id_utilisation' => $utilisation->id, 'message' => 'Original']);

        $this->actingAs($intruder, 'sanctum')
            ->putJson("/api/avis/{$avis->id}", ['message' => 'Modifié par un intrus'])
            ->assertForbidden();

        $this->assertSame('Original', $avis->fresh()->message);
    }

    public function test_another_user_cannot_delete_someone_elses_avis(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $utilisation = $this->utilisationFor($owner);
        $avis = Avis::factory()->create(['id_utilisation' => $utilisation->id]);

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/avis/{$avis->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('avis', ['id' => $avis->id]);
    }

    public function test_owner_can_update_and_delete_their_own_avis(): void
    {
        $user = User::factory()->create();
        $utilisation = $this->utilisationFor($user);
        $avis = Avis::factory()->create(['id_utilisation' => $utilisation->id]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/avis/{$avis->id}", ['message' => 'Édité par le propriétaire'])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/avis/{$avis->id}")
            ->assertOk();

        $this->assertDatabaseMissing('avis', ['id' => $avis->id]);
    }
}
