<?php

namespace Database\Factories;

use App\Models\CatSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatSite>
 */
class CatSiteFactory extends Factory
{
    protected $model = CatSite::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->word(),
        ];
    }
}
