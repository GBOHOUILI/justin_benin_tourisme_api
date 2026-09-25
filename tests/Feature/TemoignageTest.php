<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Temoignage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemoignageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_index_only_returns_actifs(): void
    {
        Temoignage::factory()->create(['nom' => 'Actif', 'actif' => true]);
        Temoignage::factory()->create(['nom' => 'Inactif', 'actif' => false]);

        $response = $this->getJson('/api/temoignages');

        $response->assertOk()->assertJsonCount(1);
        $this->assertSame('Actif', $response->json()[0]['nom']);
    }

    public function test_admin_index_returns_actifs_and_inactifs(): void
    {
        $admin = Admin::factory()->create();
        Temoignage::factory()->create(['actif' => true]);
        Temoignage::factory()->create(['actif' => false]);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/temoignages');

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_admin_can_create_temoignage_with_photo(): void
    {
        Storage::fake('public');
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->postJson('/api/admin/temoignages', [
            'nom' => 'Marie Dossou',
            'role' => 'Prestataire',
            'message' => "Totché m'a permis de doubler mes réservations.",
            'photo' => UploadedFile::fake()->create('marie.jpg', 10, 'image/jpeg'),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('temoignage', ['nom' => 'Marie Dossou', 'actif' => true]);
        Storage::disk('public')->assertExists($response->json('photo'));
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->postJson('/api/admin/temoignages', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['nom', 'role', 'message']);
    }

    public function test_guest_cannot_create_temoignage(): void
    {
        $response = $this->postJson('/api/admin/temoignages', [
            'nom' => 'Intrus', 'role' => 'Touriste', 'message' => 'x',
        ]);

        $response->assertUnauthorized();
    }

    public function test_admin_can_update_temoignage(): void
    {
        $admin = Admin::factory()->create();
        $temoignage = Temoignage::factory()->create(['actif' => true]);

        $response = $this->actingAs($admin, 'admin')->putJson("/api/admin/temoignages/{$temoignage->id}", [
            'actif' => false,
        ]);

        $response->assertOk();
        $this->assertFalse($temoignage->fresh()->actif);
    }

    public function test_admin_delete_removes_record_and_physical_file(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->create('avatar.jpg', 10, 'image/jpeg')->store('temoignages', 'public');
        $admin = Admin::factory()->create();
        $temoignage = Temoignage::factory()->create(['photo' => $path]);

        $response = $this->actingAs($admin, 'admin')->deleteJson("/api/admin/temoignages/{$temoignage->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('temoignage', ['id' => $temoignage->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
