<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSesionParlamentariaRequest;
use App\Http\Requests\UpdateSesionParlamentariaRequest;
use App\Models\SesionParlamentaria;
use App\Services\Recinto\GestorRecinto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SesionParlamentariaController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SesionParlamentaria::class);

        return view('configuracion.sesiones.panel-page', ['sesiones' => SesionParlamentaria::query()->latest('fecha_inicio_programada')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', SesionParlamentaria::class);

        return view('configuracion.sesiones.create');
    }

    public function store(StoreSesionParlamentariaRequest $request): RedirectResponse
    {
        $sesion = SesionParlamentaria::query()->create([...$request->validated(), 'estado' => 'borrador']);

        return redirect()->route('configuracion.sesiones.edit', $sesion)->with('estado', 'Sesión parlamentaria creada.');
    }

    public function edit(SesionParlamentaria $sesion): View
    {
        Gate::authorize('update', $sesion);
        $sesion->load(['votaciones' => fn ($consulta) => $consulta->latest()]);

        return view('configuracion.sesiones.edit-page', compact('sesion'));
    }

    public function update(UpdateSesionParlamentariaRequest $request, SesionParlamentaria $sesion): RedirectResponse
    {
        $sesion->update($request->validated());

        return back()->with('estado', 'Sesión parlamentaria actualizada.');
    }

    public function finalizar(SesionParlamentaria $sesion, GestorRecinto $gestor): RedirectResponse
    {
        Gate::authorize('update', $sesion);
        abort_unless($sesion->estado === 'en_curso', 422, 'Sólo puede finalizarse una sesión en curso.');
        $gestor->cerrarSesion($sesion);

        return back()->with('estado', 'Sesión parlamentaria finalizada definitivamente.');
    }

    public function reactivar(SesionParlamentaria $sesion): RedirectResponse
    {
        Gate::authorize('update', $sesion);
        abort_unless($sesion->estado === 'finalizada', 422, 'Sólo puede reactivarse una sesión finalizada.');
        $sesion->update(['estado' => 'preparada', 'finalizada_en' => null]);

        return back()->with('estado', 'Sesión reactivada y disponible para iniciar desde COMANDO.');
    }
}
