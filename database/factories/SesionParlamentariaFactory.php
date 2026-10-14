<?php

namespace Database\Factories;

use App\Models\SesionParlamentaria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SesionParlamentaria>
 */
class SesionParlamentariaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['nombre' => 'Sesión '.fake()->unique()->numerify('###'), 'numero' => fake()->numerify('###/2026'), 'fecha_inicio_programada' => now()->addDay(), 'estado' => 'borrador'];
    }
}
