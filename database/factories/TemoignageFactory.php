<?php

namespace Database\Factories;

use App\Models\Temoignage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Temoignage>
 */
class TemoignageFactory extends Factory
{
    protected $model = Temoignage::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'role' => fake()->randomElement(['Prestataire', 'Touriste', 'Responsable régional']),
            'message' => fake()->paragraph(),
            'photo' => null,
            'actif' => true,
        ];
    }
}
