<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $prix = 0;
        $nombre = fake()->numberBetween(1, 3);

        return [
            'type' => 'site',
            'prix' => $prix,
            'nombre' => $nombre,
            'total' => $prix * $nombre,
            'id_site' => Site::factory(),
            'id_user' => User::factory(),
            'statut' => 'confirmee',
        ];
    }
}
