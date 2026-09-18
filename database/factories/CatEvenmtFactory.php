<?php

namespace Database\Factories;

use App\Models\CatEvenmt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatEvenmt>
 */
class CatEvenmtFactory extends Factory
{
    protected $model = CatEvenmt::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->unique()->word(),
        ];
    }
}
