<?php

namespace Database\Factories;

use App\Models\Avis;
use App\Models\Reservation;
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
            'id_reservation' => Reservation::factory()->state(['statut' => 'confirmee']),
            'message' => fake()->sentence(),
            'status' => 'en_attente',
        ];
    }
}
