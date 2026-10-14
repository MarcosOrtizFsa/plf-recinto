<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSesionParlamentariaRequest;
use App\Http\Requests\UpdateSesionParlamentariaRequest;
use App\Models\SesionParlamentaria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SesionParlamentariaController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SesionParlamentaria::class);

        return view('configuracion.sesiones.index', ['sesiones' => SesionParlamentaria::query()->latest('fecha_inicio_programada')->paginate(15)]);
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

        return view('configuracion.sesiones.edit', compact('sesion'));
    }

    public function update(UpdateSesionParlamentariaRequest $request, SesionParlamentaria $sesion): RedirectResponse
    {
        $sesion->update($request->validated());

        return back()->with('estado', 'Sesión parlamentaria actualizada.');
    }
}
