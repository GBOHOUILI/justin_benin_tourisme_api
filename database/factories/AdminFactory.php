<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'tel' => fake()->unique()->numerify('+229#########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'status' => true,
        ];
    }
}
