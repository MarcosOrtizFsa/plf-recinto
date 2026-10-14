<?php

namespace App\Actions\Usuarios;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Arr;

class ActualizarUsuario
{
    /** Actualiza el usuario sin reemplazar la contraseña cuando quedó vacía. */
    public function ejecutar(User $usuario, array $datos): User
    {
        $rol = Rol::query()->findOrFail($datos['rol_id']);
        $datos['banca_id'] = $rol->slug === 'legisladores' ? $datos['banca_id'] : null;
        $campos = ['nombre', 'email', 'rol_id', 'banca_id', 'activo'];

        if (! empty($datos['contrasena'])) {
            $campos[] = 'contrasena';
        }

        $usuario->update(Arr::only($datos, $campos));

        return $usuario->refresh();
    }
}
