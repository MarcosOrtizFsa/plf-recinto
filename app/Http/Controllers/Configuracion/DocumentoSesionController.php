<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\DocumentoSesion;
use App\Models\SesionParlamentaria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentoSesionController extends Controller
{
    public function update(Request $request, SesionParlamentaria $sesion, Documento $documento): RedirectResponse
    {
        Gate::authorize('update', $sesion);
        Gate::authorize('view', $documento);
        $datos = $request->validate(['activo' => ['required', 'boolean']]);
        abort_if($datos['activo'] && ! $documento->activo, 422, 'No puede activarse un documento archivado.');

        $asignacion = DocumentoSesion::query()->firstOrCreate(
            ['sesion_parlamentaria_id' => $sesion->id, 'documento_id' => $documento->id],
            ['orden' => DocumentoSesion::query()->where('sesion_parlamentaria_id', $sesion->id)->max('orden') + 1],
        );
        $asignacion->update(['activo' => $datos['activo']]);

        return back()->with('estado', $datos['activo'] ? 'Documento activado para la sesión.' : 'Documento desactivado para la sesión.');
    }
}
