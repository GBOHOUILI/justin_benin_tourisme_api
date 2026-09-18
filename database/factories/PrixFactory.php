<?php

namespace Database\Factories;

use App\Models\Prix;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prix>
 */
class PrixFactory extends Factory
{
    protected $model = Prix::class;

    public function definition(): array
    {
        return [
            'libelle' => 'Adulte',
            'montant' => fake()->randomElement([0, 2000, 5000]),
            'id_site' => Site::factory(),
        ];
    }
}
