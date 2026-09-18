<?php

namespace Tests\Feature;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContenuEnrichiEntitesTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_round_trips_points_forts_inclus_non_inclus_as_arrays(): void
    {
        $site = Site::factory()->create([
            'points_forts' => ['Vue imprenable', 'Guide inclus'],
            'inclus' => ['Accès au site', 'Parking'],
            'non_inclus' => ['Transport', 'Repas'],
            'infos_pratiques' => 'Prévoir de bonnes chaussures.',
            'recommandations' => 'Venir tôt le matin pour éviter la foule.',
        ]);

        $fresh = $site->fresh();

        $this->assertSame(['Vue imprenable', 'Guide inclus'], $fresh->points_forts);
        $this->assertSame(['Accès au site', 'Parking'], $fresh->inclus);
        $this->assertSame(['Transport', 'Repas'], $fresh->non_inclus);
        $this->assertSame('Prévoir de bonnes chaussures.', $fresh->infos_pratiques);
        $this->assertSame('Venir tôt le matin pour éviter la foule.', $fresh->recommandations);
    }

    public function test_evenement_round_trips_itineraire_groupe_langue_difficulte(): void
    {
        $evenement = \App\Models\Evenement::factory()->create([
            'itineraire' => [
                ['titre' => 'Jour 1 - Arrivée', 'description' => 'Accueil à Ouidah.'],
                ['titre' => 'Jour 2 - Cérémonies', 'description' => 'Immersion Vodun.'],
            ],
            'groupe_min' => 2,
            'groupe_max' => 10,
            'langue' => 'Français, Anglais',
            'difficulte' => 'moderee',
        ]);

        $fresh = $evenement->fresh();

        $this->assertSame('Jour 1 - Arrivée', $fresh->itineraire[0]['titre']);
        $this->assertSame(2, $fresh->groupe_min);
        $this->assertSame(10, $fresh->groupe_max);
        $this->assertSame('Français, Anglais', $fresh->langue);
        $this->assertSame('moderee', $fresh->difficulte);
    }

    public function test_evenement_date_debut_stores_time_of_day(): void
    {
        $evenement = \App\Models\Evenement::factory()->create([
            'date_debut' => '2027-01-02 12:00:00',
        ]);

        $this->assertSame('12:00:00', $evenement->fresh()->date_debut->format('H:i:s'));
    }

    public function test_site_round_trips_duree_visite_et_difficulte(): void
    {
        $site = Site::factory()->create([
            'duree_visite' => '2h',
            'difficulte' => 'facile',
        ]);

        $fresh = $site->fresh();

        $this->assertSame('2h', $fresh->duree_visite);
        $this->assertSame('facile', $fresh->difficulte);
    }

    public function test_hotel_round_trips_contenu_enrichi_et_heures_arrivee_depart(): void
    {
        $hotel = \App\Models\Hotel::factory()->create([
            'points_forts' => ['Piscine', 'Vue mer'],
            'heure_arrivee' => '14:00',
            'heure_depart' => '11:00',
        ]);

        $fresh = $hotel->fresh();

        $this->assertSame(['Piscine', 'Vue mer'], $fresh->points_forts);
        $this->assertSame('14:00:00', $fresh->heure_arrivee);
        $this->assertSame('11:00:00', $fresh->heure_depart);
    }
}
