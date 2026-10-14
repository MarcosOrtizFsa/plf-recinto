<?php

namespace App\Http\Controllers\Configuracion;

use App\Actions\Documentos\CrearDocumentoManual;
use App\Contracts\AlmacenamientoDocumentos;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentoRequest;
use App\Models\Documento;
use App\Models\SesionParlamentaria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Documento::class);
        $sesiones = SesionParlamentaria::query()->latest('fecha_inicio_programada')->get();
        $sesionSeleccionada = request('sesion_id') ? $sesiones->firstWhere('id', (int) request('sesion_id')) : $sesiones->first();
        $documentosSesion = $sesionSeleccionada?->documentos()->get()->keyBy('id') ?? collect();
        $documentos = Documento::query()->with('creador')->latest()->paginate(15)->withQueryString();

        return view('configuracion.documentos.index', compact('documentos', 'sesiones', 'sesionSeleccionada', 'documentosSesion'));
    }

    public function create(): View
    {
        Gate::authorize('create', Documento::class);

        return view('configuracion.documentos.create');
    }

    public function store(StoreDocumentoRequest $request, CrearDocumentoManual $crear): RedirectResponse
    {
        $crear->ejecutar($request->string('titulo')->toString(), $request->file('archivo'), $request->user());

        return redirect()->route('configuracion.documentos.index')->with('estado', 'PDF cargado en almacenamiento privado.');
    }

    public function preview(Documento $documento, AlmacenamientoDocumentos $almacenamiento): StreamedResponse
    {
        Gate::authorize('view', $documento);

        return $almacenamiento->visualizar($documento->ruta, $documento->nombre_archivo);
    }

    public function cambiarEstado(Documento $documento): RedirectResponse
    {
        Gate::authorize('update', $documento);
        $documento->update(['activo' => ! $documento->activo]);
        if (! $documento->activo) {
            $documento->sesiones()->updateExistingPivot($documento->sesiones()->pluck('sesiones_parlamentarias.id'), ['activo' => false]);
        }

        return back()->with('estado', $documento->activo ? 'Documento habilitado.' : 'Documento archivado y desactivado en todas las sesiones.');
    }
}
