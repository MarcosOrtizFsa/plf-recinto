<?php

namespace Database\Factories;

use App\Models\Asistencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asistencia>
 */
class AsistenciaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['sesion_parlamentaria_id' => SesionParlamentariaFactory::new(), 'usuario_id' => UserFactory::new(), 'estado' => 'presente', 'registrada_en' => now()];
    }
}
