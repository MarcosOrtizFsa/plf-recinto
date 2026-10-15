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

    public function suspenderSesion(SesionParlamentaria $sesion, GestorRecinto $gestor): RedirectResponse
    {
        $this->autorizar();
        $gestor->suspenderSesion($sesion);

        return back()->with('estado', 'Ecosistema del recinto suspendido. La sesión parlamentaria continúa en curso.');
    }

    public function reanudarSesion(SesionParlamentaria $sesion, GestorRecinto $gestor): RedirectResponse
    {
        $this->autorizar();
        $gestor->reanudarSesion($sesion);

        return back()->with('estado', 'Ecosistema del recinto reanudado.');
    }

    public function asistencia(RegistrarAsistenciaRequest $request, SesionParlamentaria $sesion, GestorRecinto $gestor): RedirectResponse|JsonResponse
    {
        $gestor->registrarAsistencia($sesion, User::query()->findOrFail($request->integer('usuario_id')), $request->string('estado')->toString(), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['estado' => 'Asistencia actualizada.']);
        }

        return back()->with('estado', 'Asistencia actualizada.');
    }

    public function reiniciarAsistencia(Request $request, SesionParlamentaria $sesion, GestorRecinto $gestor): RedirectResponse|JsonResponse
    {
        $this->autorizar();
        $gestor->reiniciarAsistencia($sesion);

        if ($request->expectsJson()) {
            return response()->json(['estado' => 'Asistencia reiniciada.']);
        }

        return back()->with('estado', 'Asistencia reiniciada.');
    }

    public function banca(Request $request, SesionParlamentaria $sesion, Banca $banca, GestorRecinto $gestor): RedirectResponse
    {
        $this->autorizar();
        $datos = $request->validate(['activa' => ['required', 'boolean'], 'motivo' => ['nullable', 'string', 'max:255']]);
        $gestor->cambiarBanca($sesion, $banca, (bool) $datos['activa'], $datos['motivo'] ?? null);

        return back()->with('estado', 'Estado de banca actualizado.');
    }

    public function documento(Request $request, SesionParlamentaria $sesion, Documento $documento, GestorRecinto $gestor): RedirectResponse|JsonResponse
    {
        $this->autorizar();
        $gestor->mostrarDocumento($sesion, $documento);

        if ($request->expectsJson()) {
            return response()->json(['estado' => 'Documento publicado en las bancas.']);
        }

        return back()->with('estado', 'Documento publicado en las bancas.');
    }

    public function crearVotacion(StoreVotacionRequest $request): RedirectResponse
    {
        Votacion::query()->create([...$request->validated(), 'estado' => 'configurada']);

        return back()->with('estado', 'Votación configurada.');
    }

    public function iniciarVotacion(IniciarVotacionRequest $request, Votacion $votacion, GestorRecinto $gestor): RedirectResponse|JsonResponse
    {
        $gestor->iniciarVotacion($votacion, $request->integer('duracion_segundos'), $request->string('metodo')->toString());

        if ($request->expectsJson()) {
            return response()->json(['estado' => 'Votación iniciada.']);
        }

        return back()->with('estado', 'Votación iniciada.');
    }

    public function cerrarVotacion(Request $request, Votacion $votacion, GestorRecinto $gestor): RedirectResponse|JsonResponse
    {
        $this->autorizar();
        $gestor->cerrarVotacion($votacion);

        if ($request->expectsJson()) {
            return response()->json(['estado' => 'Votación cerrada.']);
        }

        return back()->with('estado', 'Votación cerrada. El resultado aún no fue publicado.');
    }

    public function reiniciarVotacion(Request $request, Votacion $votacion, GestorRecinto $gestor): RedirectResponse|JsonResponse
    {
        $this->autorizar();
        $gestor->reiniciarVotacion($votacion);

        if ($request->expectsJson()) {
            return response()->json(['estado' => 'Votación reiniciada y lista para volver a iniciarse.']);
        }

        return back()->with('estado', 'Votación reiniciada. Se eliminaron todos los votos anteriores.');
    }

    public function publicarResultado(Request $request, Votacion $votacion, GestorRecinto $gestor): RedirectResponse|JsonResponse
    {
        $this->autorizar();
        $gestor->publicarResultado($votacion);

        if ($request->expectsJson()) {
            return response()->json(['estado' => 'Resultado publicado.']);
        }

        return back()->with('estado', 'Resultado publicado en el monitor.');
    }

    public function visibilidadVotacion(Request $request, Votacion $votacion, GestorRecinto $gestor): JsonResponse
    {
        $this->autorizar();
        $datos = $request->validate(['visible' => ['required', 'boolean']]);
        $gestor->mostrarVotacionEnVivo($votacion, (bool) $datos['visible']);

        return response()->json(['estado' => $datos['visible'] ? 'Votación visible en el monitor.' : 'Votación oculta en el monitor.']);
    }

    public function despublicarResultado(Request $request, Votacion $votacion, GestorRecinto $gestor): RedirectResponse|JsonResponse
    {
        $this->autorizar();
        $gestor->despublicarResultado($votacion);

        if ($request->expectsJson()) {
            return response()->json(['estado' => 'Resultado retirado del monitor.']);
        }

        return back()->with('estado', 'Resultado retirado. El monitor volvió a Asistencia.');
    }

    public function votoAsistido(RegistrarVotoRequest $request, Votacion $votacion, GestorRecinto $gestor): RedirectResponse|JsonResponse
    {
        $this->autorizar();
        abort_unless($votacion->metodo === 'asistido', 422);
        $gestor->votar($votacion, User::query()->findOrFail($request->integer('usuario_id')), $request->string('opcion')->toString(), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['estado' => 'Voto registrado.']);
        }

        return back()->with('estado', 'Voto registrado. Puede corregirse mientras la votación continúe abierta.');
    }

    private function autorizar(): void
    {
        abort_unless(request()->user()?->puedeConfigurarRecinto(), 403);
    }
}
