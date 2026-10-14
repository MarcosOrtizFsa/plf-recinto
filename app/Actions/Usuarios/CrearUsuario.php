<?php

namespace App\Actions\Usuarios;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Arr;

class CrearUsuario
{
    /** Crea un usuario normalizando la asignación de banca según su rol. */
    public function ejecutar(array $datos): User
    {
        $rol = Rol::query()->findOrFail($datos['rol_id']);
        $datos['banca_id'] = $rol->slug === 'legisladores' ? $datos['banca_id'] : null;

        return User::query()->create(Arr::only($datos, ['nombre', 'email', 'rol_id', 'banca_id', 'contrasena', 'activo']));
    }
}
