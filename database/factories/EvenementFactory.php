<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\CatEvenmt;
use App\Models\Evenement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evenement>
 */
class EvenementFactory extends Factory
{
    protected $model = Evenement::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->sentence(3),
            'adresse' => fake()->address(),
            'longitude' => fake()->longitude(1, 3),
            'latitude' => fake()->latitude(6, 12),
            'description' => fake()->sentence(),
            'date_debut' => now()->addMonth(),
            'date_fin' => now()->addMonth()->addDays(3),
            'status' => 'valide',
            'id_cat_evenmt' => CatEvenmt::factory(),
            'id_admin' => Admin::factory(),
        ];
    }
}
