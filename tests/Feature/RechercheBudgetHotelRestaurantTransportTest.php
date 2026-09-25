<?php

namespace Tests\Feature;

use App\Models\Chambre;
use App\Models\Hotel;
use App\Models\Plat;
use App\Models\Restaurant;
use App\Models\Trajet;
use App\Models\Transport;
use App\Models\Ville;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Chantier "recherche proximité/budget" étendu à Hôtel/Restaurant/Transport
// (la proximité lat/lng existait déjà depuis le module Hôtel/Restaurant/
// Transport initial - seul le filtre budget manquait, cf. ROADMAP). Le prix
// vit sur la sous-entité (Chambre/Plat/Trajet), jamais directement sur la
// fiche parente, contrairement à Site/Evenement qui ont leur propre Prix.
class RechercheBudgetHotelRestaurantTransportTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_prix_min_max_filters_on_chambre_prix_nuit(): void
    {
        $cher = Hotel::factory()->create(['status' => 'valide']);
        Chambre::create(['id_hotel' => $cher->id, 'type_chambre' => 'Suite', 'prix_nuit' => 150000, 'disponibilite' => true]);

        $pasCher = Hotel::factory()->create(['status' => 'valide']);
        Chambre::create(['id_hotel' => $pasCher->id, 'type_chambre' => 'Standard', 'prix_nuit' => 20000, 'disponibilite' => true]);

        $response = $this->getJson('/api/hotels?prix_max=50000');

        $ids = collect($response->json('data'))->pluck('id');
        $response->assertOk();
        $this->assertTrue($ids->contains($pasCher->id));
        $this->assertFalse($ids->contains($cher->id));
    }

    public function test_restaurant_prix_min_max_filters_on_plat_prix(): void
    {
        $cher = Restaurant::factory()->create(['status' => 'valide']);
        Plat::create(['id_restaurant' => $cher->id, 'nom' => 'Menu dégustation', 'prix' => 22000]);

        $pasCher = Restaurant::factory()->create(['status' => 'valide']);
        Plat::create(['id_restaurant' => $pasCher->id, 'nom' => 'Plat du jour', 'prix' => 4500]);

        $response = $this->getJson('/api/restaurants?prix_min=10000');

        $ids = collect($response->json('data'))->pluck('id');
        $response->assertOk();
        $this->assertTrue($ids->contains($cher->id));
        $this->assertFalse($ids->contains($pasCher->id));
    }

    public function test_transport_prix_min_max_filters_on_trajet_prix(): void
    {
        $villeA = Ville::create(['nom' => 'Cotonou']);
        $villeB = Ville::create(['nom' => 'Natitingou']);

        $cher = Transport::factory()->create(['status' => 'valide']);
        Trajet::create(['id_transport' => $cher->id, 'id_ville_depart' => $villeA->id, 'id_ville_arrivee' => $villeB->id, 'horaire_depart' => '06:30', 'prix' => 12000]);

        $pasCher = Transport::factory()->create(['status' => 'valide']);
        Trajet::create(['id_transport' => $pasCher->id, 'id_ville_depart' => $villeA->id, 'id_ville_arrivee' => $villeB->id, 'horaire_depart' => '13:00', 'prix' => 3500]);

        $response = $this->getJson('/api/transports?prix_max=5000');

        $ids = collect($response->json('data'))->pluck('id');
        $response->assertOk();
        $this->assertTrue($ids->contains($pasCher->id));
        $this->assertFalse($ids->contains($cher->id));
    }
}
