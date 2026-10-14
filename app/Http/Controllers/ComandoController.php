<?php

namespace App\Http\Controllers;

use App\Http\Requests\IniciarVotacionRequest;
use App\Http\Requests\RegistrarAsistenciaRequest;
use App\Http\Requests\RegistrarVotoRequest;
use App\Http\Requests\StoreVotacionRequest;
use App\Models\Banca;
use App\Models\Documento;
use App\Models\SesionParlamentaria;
use App\Models\User;
use App\Models\Votacion;
use App\Services\Recinto\ConsultaEstadoRecinto;
use App\Services\Recinto\GestorRecinto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComandoController extends Controller
{
    public function index(ConsultaEstadoRecinto $consulta): View
    {
        $this->autorizar();
        $sesion = SesionParlamentaria::query()->where('estado', 'en_curso')->first();

        return view('comando.index', [
            'sesionActiva' => $sesion,
            'sesiones' => SesionParlamentaria::query()->whereIn('estado', ['borrador', 'preparada'])->orderBy('fecha_inicio_programada')->get(),
            'documentos' => $sesion?->documentos()->wherePivot('activo', true)->where('documentos.activo', true)->get() ?? collect(),
            'votaciones' => $sesion?->votaciones()->latest()->with('votos')->get() ?? collect(),
            'estadoInicial' => $consulta->comando(),
        ]);
    }

    public function estado(ConsultaEstadoRecinto $consulta): JsonResponse
    {
        $this->autorizar();

        return response()->json($consulta->comando());
    }

    public function activarSesion(SesionParlamentaria $sesion, GestorRecinto $gestor): RedirectResponse
    {
        $this->autorizar();
        $gestor->activarSesion($sesion);

        return back()->with('estado', 'Sesión parlamentaria iniciada.');
    }

    public function cerrarSesion(SesionParlamentaria $sesion, GestorRecinto $gestor): RedirectResponse
    {
        $this->autorizar();
        $gestor->cerrarSesion($sesion);

        return back()->with('estado', 'Sesión parlamentaria finalizada.');
    }

    public function asistencia(RegistrarAsistenciaRequest $request, SesionParlamentaria $sesion, GestorRecinto $gestor): RedirectResponse
    {
        $gestor->registrarAsistencia($sesion, User::query()->findOrFail($request->integer('usuario_id')), $request->string('estado')->toString(), $request->user());

        return back()->with('estado', 'Asistencia actualizada.');
    }

    public function banca(Request $request, SesionParlamentaria $sesion, Banca $banca, GestorRecinto $gestor): RedirectResponse
    {
        $this->autorizar();
        $datos = $request->validate(['activa' => ['required', 'boolean'], 'motivo' => ['nullable', 'string', 'max:255']]);
        $gestor->cambiarBanca($sesion, $banca, (bool) $datos['activa'], $datos['motivo'] ?? null);

        return back()->with('estado', 'Estado de banca actualizado.');
    }

    public function documento(SesionParlamentaria $sesion, Documento $documento, GestorRecinto $gestor): RedirectResponse
    {
        $this->autorizar();
        $gestor->mostrarDocumento($sesion, $documento);

        return back()->with('estado', 'Documento publicado en las bancas.');
    }

    public function crearVotacion(StoreVotacionRequest $request): RedirectResponse
    {
        Votacion::query()->create([...$request->validated(), 'estado' => 'configurada']);

        return back()->with('estado', 'Votación configurada.');
    }

    public function iniciarVotacion(IniciarVotacionRequest $request, Votacion $votacion, GestorRecinto $gestor): RedirectResponse
    {
        $gestor->iniciarVotacion($votacion, $request->integer('duracion_segundos'));

        return back()->with('estado', 'Votación iniciada.');
    }

    public function cerrarVotacion(Votacion $votacion, GestorRecinto $gestor): RedirectResponse
    {
        $this->autorizar();
        $gestor->cerrarVotacion($votacion);

        return back()->with('estado', 'Votación cerrada y resultado publicado.');
    }

    public function votoAsistido(RegistrarVotoRequest $request, Votacion $votacion, GestorRecinto $gestor): RedirectResponse
    {
        $this->autorizar();
        abort_unless($votacion->metodo === 'asistido', 422);
        $gestor->votar($votacion, User::query()->findOrFail($request->integer('usuario_id')), $request->string('opcion')->toString(), $request->user());

        return back()->with('estado', 'Voto registrado. Puede corregirse mientras la votación continúe abierta.');
    }

    private function autorizar(): void
    {
        abort_unless(request()->user()?->puedeConfigurarRecinto(), 403);
    }
}
