<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\Favori;
use App\Models\Hotel;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_a_site_to_favorites(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/favoris', [
            'type' => 'site',
            'id' => $site->id,
        ]);

        $response->assertCreated()->assertJson(['type' => 'site']);
        $this->assertDatabaseHas('favori', ['id_user' => $user->id, 'id_site' => $site->id]);
    }

    public function test_adding_the_same_favorite_twice_is_idempotent(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/favoris', ['type' => 'site', 'id' => $site->id]);
        $this->actingAs($user, 'sanctum')->postJson('/api/favoris', ['type' => 'site', 'id' => $site->id]);

        $this->assertSame(1, Favori::where('id_user', $user->id)->count());
    }

    public function test_store_rejects_nonexistent_reference(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/favoris', [
            'type' => 'hotel',
            'id' => 999999,
        ]);

        $response->assertStatus(404);
    }

    public function test_index_returns_favorites_with_loaded_item_grouped_by_type(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();
        $evenement = Evenement::factory()->create();

        Favori::create(['id_user' => $user->id, 'id_site' => $site->id]);
        Favori::create(['id_user' => $user->id, 'id_evnmt' => $evenement->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/favoris');

        $response->assertOk()->assertJsonCount(2);
        $types = collect($response->json())->pluck('type')->sort()->values();
        $this->assertSame(['evenement', 'site'], $types->all());
    }

    public function test_index_only_returns_the_authenticated_users_favorites(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $site = Site::factory()->create();
        Favori::create(['id_user' => $other->id, 'id_site' => $site->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/favoris');

        $response->assertOk()->assertJsonCount(0);
    }

    public function test_user_can_remove_their_own_favorite(): void
    {
        $user = User::factory()->create();
        $hotel = Hotel::factory()->create();
        $favori = Favori::create(['id_user' => $user->id, 'id_hotel' => $hotel->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/favoris/{$favori->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('favori', ['id' => $favori->id]);
    }

    public function test_user_cannot_remove_someone_elses_favorite(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $site = Site::factory()->create();
        $favori = Favori::create(['id_user' => $owner->id, 'id_site' => $site->id]);

        $response = $this->actingAs($intruder, 'sanctum')->deleteJson("/api/favoris/{$favori->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('favori', ['id' => $favori->id]);
    }

    public function test_guest_cannot_access_favorites(): void
    {
        $this->getJson('/api/favoris')->assertUnauthorized();
        $this->postJson('/api/favoris', ['type' => 'site', 'id' => 1])->assertUnauthorized();
    }
}
