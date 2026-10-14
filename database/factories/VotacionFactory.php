<?php

namespace Database\Factories;

use App\Models\Votacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Votacion>
 */
class VotacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['sesion_parlamentaria_id' => SesionParlamentariaFactory::new(), 'tema' => fake()->sentence(), 'metodo' => fake()->randomElement(['asistido', 'directo']), 'estado' => 'configurada', 'duracion_segundos' => 60];
    }
}
