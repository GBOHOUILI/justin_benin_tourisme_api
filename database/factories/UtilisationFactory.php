<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\Utilisation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Utilisation>
 */
class UtilisationFactory extends Factory
{
    protected $model = Utilisation::class;

    public function definition(): array
    {
        return [
            'date_visite' => fake()->date(),
            'heure' => fake()->time(),
            'id_ticket' => Ticket::factory(),
        ];
    }
}
