<?php

namespace App\Http\Controllers;

use App\Actions\Usuarios\ActualizarUsuario;
use App\Actions\Usuarios\CrearUsuario;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\Banca;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);
        $usuarios = User::query()
            ->with(['rol', 'banca'])
            ->when(request('buscar'), fn ($consulta, $buscar) => $consulta->where(fn ($q) => $q->where('nombre', 'like', "%{$buscar}%")->orWhere('email', 'like', "%{$buscar}%")))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('usuarios.create', $this->datosFormulario());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUsuarioRequest $request, CrearUsuario $crearUsuario): RedirectResponse
    {
        $usuario = $crearUsuario->ejecutar($request->validated());

        return redirect()->route('usuarios.edit', $usuario)->with('estado', 'Usuario creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $usuario): View
    {
        Gate::authorize('update', $usuario);

        return view('usuarios.edit', ['usuario' => $usuario, ...$this->datosFormulario($usuario)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUsuarioRequest $request, User $usuario, ActualizarUsuario $actualizarUsuario): RedirectResponse
    {
        $actualizarUsuario->ejecutar($usuario, $request->validated());

        return redirect()->route('usuarios.edit', $usuario)->with('estado', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $usuario): RedirectResponse
    {
        Gate::authorize('delete', $usuario);
        $usuario->forceFill(['activo' => false, 'token_recordarme' => null])->save();

        return redirect()->route('usuarios.index')->with('estado', 'Usuario desactivado. Se preservó su historial legislativo.');
    }

    /** Datos compartidos por los formularios de alta y edición. */
    private function datosFormulario(?User $usuario = null): array
    {
        $bancas = Banca::query()
            ->where('activa', true)
            ->where(fn ($consulta) => $consulta->whereDoesntHave('usuario')->when($usuario?->banca_id, fn ($q, $bancaId) => $q->orWhereKey($bancaId)))
            ->orderBy('numero')
            ->get();

        return ['roles' => Rol::query()->orderBy('nombre')->get(), 'bancas' => $bancas];
    }
}
