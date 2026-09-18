<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->company(),
            'adresse' => fake()->address(),
            'longitude' => fake()->longitude(1, 3),
            'latitude' => fake()->latitude(6, 12),
            'description' => fake()->sentence(),
            'type_cuisine' => 'Béninoise',
            'gamme_prix' => 'moyen',
            'status' => 'valide',
            'id_admin' => Admin::factory(),
        ];
    }
}
