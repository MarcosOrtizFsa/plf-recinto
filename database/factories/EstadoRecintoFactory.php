<?php

namespace Database\Factories;

use App\Models\EstadoRecinto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstadoRecinto>
 */
class EstadoRecintoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['sesion_parlamentaria_id' => SesionParlamentariaFactory::new(), 'vista_monitor' => 'espera', 'version' => 1];
    }
}
