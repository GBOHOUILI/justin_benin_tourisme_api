<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Prestataire;
use App\Models\Region;
use App\Models\ResponsableRegional;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Couvre la nouvelle action "demander des précisions" (entre en_attente et
 * rejete : le responsable ne rejette pas d'emblée, il peut demander un
 * complément d'info au prestataire) sur Site, avec un test de non-régression
 * ("smoke test") sur Hotel pour vérifier que la même logique, copiée dans les
 * 5 contrôleurs (Site/Evenement/Hotel/Restaurant/Transport), fonctionne
 * partout de façon identique.
 */
class DemanderPrecisionsTest extends TestCase
{
    use RefreshDatabase;

    private function prestataire(): Prestataire
    {
        return Prestataire::create([
            'nom_entreprise' => 'Test SARL',
            'email' => 'presta-' . uniqid() . '@test.local',
            'tel' => '01' . rand(10000000, 99999999),
            'password' => 'password',
            'type_prestataire' => 'site',
            'status' => 1,
        ]);
    }

    private function responsable(?int $idRegion): ResponsableRegional
    {
        return ResponsableRegional::create([
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'tel' => '06' . rand(10000000, 99999999),
            'password' => 'password',
            'status' => 1,
            'id_region' => $idRegion,
        ]);
    }

    public function test_responsable_can_demander_precisions_on_site_in_his_region(): void
    {
        $region = Region::create(['nom' => 'Littoral']);
        $prestataire = $this->prestataire();
        $site = Site::factory()->create([
            'status' => 'en_attente',
            'id_prestataire' => $prestataire->id,
            'id_responsable' => null,
            'id_region' => $region->id,
        ]);
        $responsable = $this->responsable($region->id);

        $response = $this->actingAs($responsable, 'responsable')
            ->patchJson("/api/responsable/sites/{$site->id}/demander-precisions", [
                'commentaire' => "Merci d'ajouter une photo de la façade.",
            ]);

        $response->assertOk()->assertJsonPath('site.status', 'precisions_demandees');
        $this->assertDatabaseHas('site', [
            'id' => $site->id,
            'status' => 'precisions_demandees',
            'commentaire_responsable' => "Merci d'ajouter une photo de la façade.",
        ]);
    }

    public function test_commentaire_is_required(): void
    {
        $region = Region::create(['nom' => 'Littoral']);
        $site = Site::factory()->create(['status' => 'en_attente', 'id_region' => $region->id, 'id_responsable' => null]);
        $responsable = $this->responsable($region->id);

        $response = $this->actingAs($responsable, 'responsable')
            ->patchJson("/api/responsable/sites/{$site->id}/demander-precisions", []);

        $response->assertStatus(422)->assertJsonValidationErrors('commentaire');
    }

    public function test_responsable_cannot_target_site_outside_his_region(): void
    {
        $regionA = Region::create(['nom' => 'Littoral']);
        $regionB = Region::create(['nom' => 'Atacora']);
        $site = Site::factory()->create(['status' => 'en_attente', 'id_region' => $regionB->id, 'id_responsable' => null]);
        $responsable = $this->responsable($regionA->id);

        $response = $this->actingAs($responsable, 'responsable')
            ->patchJson("/api/responsable/sites/{$site->id}/demander-precisions", ['commentaire' => 'Précisez svp']);

        $response->assertStatus(403);
    }

    public function test_prestataire_update_after_precisions_demandees_requeues_for_review(): void
    {
        $region = Region::create(['nom' => 'Littoral']);
        $prestataire = $this->prestataire();
        $site = Site::factory()->create([
            'status' => 'precisions_demandees',
            'commentaire_responsable' => 'Ajoutez une photo',
            'id_prestataire' => $prestataire->id,
            'id_responsable' => null,
            'id_region' => $region->id,
        ]);

        $response = $this->actingAs($prestataire, 'prestataire')
            ->putJson("/api/prestataire/sites/{$site->id}", [
                'description' => 'Description mise à jour avec la photo demandée.',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('site', [
            'id' => $site->id,
            'status' => 'en_attente',
            'commentaire_responsable' => null,
        ]);
    }

    public function test_valider_clears_any_pending_commentaire(): void
    {
        $region = Region::create(['nom' => 'Littoral']);
        $site = Site::factory()->create([
            'status' => 'precisions_demandees',
            'commentaire_responsable' => 'Ajoutez une photo',
            'id_region' => $region->id,
            'id_responsable' => null,
        ]);
        $responsable = $this->responsable($region->id);

        $response = $this->actingAs($responsable, 'responsable')
            ->patchJson("/api/responsable/sites/{$site->id}/valider");

        $response->assertOk();
        $this->assertDatabaseHas('site', ['id' => $site->id, 'status' => 'valide', 'commentaire_responsable' => null]);
    }

    public function test_demander_precisions_works_on_hotel_too(): void
    {
        $region = Region::create(['nom' => 'Littoral']);
        $hotel = Hotel::factory()->create(['status' => 'en_attente', 'id_region' => $region->id, 'id_responsable' => null]);
        $responsable = $this->responsable($region->id);

        $response = $this->actingAs($responsable, 'responsable')
            ->patchJson("/api/responsable/hotels/{$hotel->id}/demander-precisions", [
                'commentaire' => 'Précisez les tarifs des chambres.',
            ]);

        $response->assertOk()->assertJsonPath('hotel.status', 'precisions_demandees');
    }
}
