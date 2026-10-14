<?php

namespace Database\Factories;

use App\Models\Voto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voto>
 */
class VotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['votacion_id' => VotacionFactory::new(), 'usuario_id' => UserFactory::new(), 'opcion' => fake()->randomElement(['afirmativo', 'negativo', 'abstencion']), 'emitido_en' => now()];
    }
}
