<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\CatSite;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->city(),
            'adresse' => fake()->address(),
            'longitude' => fake()->longitude(1, 3),
            'latitude' => fake()->latitude(6, 12),
            'description' => fake()->sentence(),
            'status' => 'valide',
            'id_cat_site' => CatSite::factory(),
            'id_admin' => Admin::factory(),
        ];
    }
}
