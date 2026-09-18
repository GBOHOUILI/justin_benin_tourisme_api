<?php

namespace Database\Factories;

use App\Models\Avis;
use App\Models\Utilisation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Avis>
 */
class AvisFactory extends Factory
{
    protected $model = Avis::class;

    public function definition(): array
    {
        return [
            'id_utilisation' => Utilisation::factory(),
            'message' => fake()->sentence(),
            'status' => 'en_attente',
        ];
    }
}
