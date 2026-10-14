<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            ['Root', 'root', 'Administración total del sistema'],
            ['Presidente', 'presidente', 'Presidencia de la Cámara'],
            ['Secretaría', 'secretaria', 'Operación del panel Comando'],
            ['Autoridades', 'autoridades', 'Autoridades parlamentarias'],
            ['Legisladores', 'legisladores', 'Diputados y diputadas con banca'],
            ['Invitados', 'invitados', 'Acceso limitado de invitados'],
        ])->each(fn (array $rol) => Rol::query()->updateOrCreate(['slug' => $rol[1]], ['nombre' => $rol[0], 'descripcion' => $rol[2]]));
    }
}
