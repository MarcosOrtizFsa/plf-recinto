<?php

namespace Database\Factories;

use App\Models\Banca;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banca>
 */
class BancaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $numero = fake()->unique()->numberBetween(1, 30);

        return ['numero' => $numero, 'nombre' => "Banca {$numero}", 'activa' => true];
    }
}
