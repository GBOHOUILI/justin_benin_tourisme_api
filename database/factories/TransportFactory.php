<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Transport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transport>
 */
class TransportFactory extends Factory
{
    protected $model = Transport::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->company(),
            'adresse' => fake()->address(),
            'longitude' => fake()->longitude(1, 3),
            'latitude' => fake()->latitude(6, 12),
            'description' => fake()->sentence(),
            'type_transport' => 'Bus',
            'capacite' => fake()->numberBetween(4, 50),
            'status' => 'valide',
            'id_admin' => Admin::factory(),
        ];
    }
}
