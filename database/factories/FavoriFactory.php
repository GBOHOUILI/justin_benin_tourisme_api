<?php

namespace Database\Factories;

use App\Models\Favori;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Favori>
 */
class FavoriFactory extends Factory
{
    protected $model = Favori::class;

    // Type par défaut 'site' - les autres types se couvrent en passant
    // explicitement id_site: null, id_hotel: X, etc. à ->create([...]).
    public function definition(): array
    {
        return [
            'id_user' => User::factory(),
            'id_site' => Site::factory(),
        ];
    }
}
