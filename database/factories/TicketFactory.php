<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'numero' => 'TCK-' . strtoupper(Str::random(8)),
            'id_reservation' => Reservation::factory(),
        ];
    }
}
