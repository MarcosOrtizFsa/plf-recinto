<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\SesionParlamentaria;
use App\Models\Votacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VotacionController extends Controller
{
    public function store(Request $request, SesionParlamentaria $sesion): RedirectResponse
    {
        Gate::authorize('update', $sesion);
        $datos = $request->validate(['tema' => ['required', 'string', 'max:255']]);

        $sesion->votaciones()->create([
            'tema' => $datos['tema'],
            'tipo' => 'general',
            'cantidad_articulos' => 1,
            'metodo' => 'directo',
            'estado' => 'configurada',
            'duracion_segundos' => 60,
        ]);

        return back()->with('estado', 'Votación EN GENERAL agregada a la sesión.');
    }

    public function destroy(SesionParlamentaria $sesion, Votacion $votacion): RedirectResponse
    {
        Gate::authorize('update', $sesion);
        abort_unless($votacion->sesion_parlamentaria_id === $sesion->id, 404);
        abort_unless($votacion->estado === 'configurada' && ! $votacion->votos()->exists(), 422, 'La votación ya fue utilizada y no puede eliminarse.');
        $votacion->delete();

        return back()->with('estado', 'Configuración de votación eliminada.');
    }
}
