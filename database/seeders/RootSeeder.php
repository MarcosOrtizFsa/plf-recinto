<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class RootSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nombre = config('recinto.root.nombre');
        $email = config('recinto.root.email');
        $contrasena = config('recinto.root.contrasena');

        if (! $nombre || ! $email || ! $contrasena) {
            throw new RuntimeException('Defina ROOT_NOMBRE, ROOT_EMAIL y ROOT_CONTRASENA antes de ejecutar los seeders.');
        }

        $rol = Rol::query()->where('slug', 'root')->firstOrFail();
        User::query()->updateOrCreate(['email' => $email], ['rol_id' => $rol->id, 'nombre' => $nombre, 'contrasena' => $contrasena, 'activo' => true]);
    }
}
