<?php

namespace Database\Factories;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Documento>
 */
class DocumentoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $archivo = fake()->uuid().'.pdf';

        return ['titulo' => fake()->sentence(4), 'nombre_archivo' => $archivo, 'ruta' => "documentos/{$archivo}", 'origen' => 'manual', 'mime_type' => 'application/pdf', 'tamano_bytes' => 1024, 'activo' => true];
    }
}
