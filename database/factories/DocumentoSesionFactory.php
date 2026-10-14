<?php

namespace Database\Factories;

use App\Models\DocumentoSesion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentoSesion>
 */
class DocumentoSesionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['sesion_parlamentaria_id' => SesionParlamentariaFactory::new(), 'documento_id' => DocumentoFactory::new(), 'orden' => 1, 'activo' => true];
    }
}
