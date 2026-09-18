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
}
