<?php

namespace Database\Seeders;

use App\Models\Banca;
use App\Models\Rol;
use App\Models\SesionParlamentaria;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SimulacroRecintoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([RolesSeeder::class, BancasSeeder::class]);
        $contrasena = config('recinto.simulacro.contrasena');
        if (! $contrasena) {
            throw new RuntimeException('Defina DEMO_CONTRASENA antes de cargar los datos de simulacro.');
        }

        $rol = Rol::query()->where('slug', 'legisladores')->firstOrFail();
        $nombres = [
            'Abril Benítez', 'Adrián Cabral', 'Ailén Cáceres', 'Bruno Cardozo', 'Camila Céspedes',
            'Damián Duarte', 'Elena Escobar', 'Esteban Fernández', 'Florencia Ferreira', 'Gabriel Franco',
            'Helena Giménez', 'Ignacio González', 'Jimena Insfrán', 'Joaquín Leiva', 'Karen López',
            'Lautaro Martínez', 'Luciana Medina', 'Mateo Méndez', 'Milagros Molina', 'Nicolás Morel',
            'Olivia Núñez', 'Pablo Ortiz', 'Renata Oviedo', 'Rodrigo Peralta', 'Sofía Quiroga',
            'Tomás Ramírez', 'Valentina Ríos', 'Walter Rojas', 'Ximena Sosa', 'Yamila Vera',
        ];

        foreach ($nombres as $indice => $nombre) {
            $numero = $indice + 1;
            $banca = Banca::query()->where('numero', $numero)->firstOrFail();
            $email = sprintf('banca%02d@recinto.test', $numero);
            $ocupante = $banca->usuario;
            if ($ocupante && $ocupante->email !== $email) {
                throw new RuntimeException("La banca {$numero} ya pertenece a {$ocupante->email}; no fue reemplazada.");
            }

            User::query()->updateOrCreate(
                ['email' => $email],
                ['nombre' => $nombre, 'rol_id' => $rol->id, 'banca_id' => $banca->id, 'contrasena' => $contrasena, 'activo' => true],
            );
        }

        SesionParlamentaria::query()->updateOrCreate(
            ['numero' => 'SIM-001/2026'],
            ['nombre' => 'Sesión de Simulacro del Recinto', 'fecha_inicio_programada' => now()->addHour(), 'estado' => 'borrador', 'observaciones' => 'Datos ficticios para validar asistencia, bancas, documentos y votaciones.'],
        );
    }
}
