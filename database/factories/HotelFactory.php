<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    protected $model = Hotel::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->company(),
            'adresse' => fake()->address(),
            'longitude' => fake()->longitude(1, 3),
            'latitude' => fake()->latitude(6, 12),
            'description' => fake()->sentence(),
            'nombre_etoiles' => fake()->numberBetween(1, 5),
            'status' => 'valide',
            'id_admin' => Admin::factory(),
        ];
    }
}
