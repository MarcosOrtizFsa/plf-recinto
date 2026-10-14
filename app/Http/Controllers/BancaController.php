<?php

namespace App\Http\Controllers;

use App\Contracts\AlmacenamientoDocumentos;
use App\Http\Requests\RegistrarVotoRequest;
use App\Models\Banca;
use App\Models\Documento;
use App\Models\EstadoRecinto;
use App\Models\User;
use App\Models\Votacion;
use App\Services\Recinto\ConsultaEstadoRecinto;
use App\Services\Recinto\GestorRecinto;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BancaController extends Controller
{
    public function show(Banca $banca): View
    {
        $this->autorizar($banca);

        return view('bancas.show', ['banca' => $banca, 'legislador' => $banca->usuario]);
    }

    public function estado(Banca $banca, ConsultaEstadoRecinto $consulta): JsonResponse
    {
        $usuario = $this->autorizar($banca);

        return response()->json($consulta->banca($banca, $usuario));
    }

    public function votar(RegistrarVotoRequest $request, Banca $banca, Votacion $votacion, GestorRecinto $gestor): JsonResponse
    {
        $usuario = $this->autorizar($banca);
        abort_unless($votacion->metodo === 'directo', 422);
        $gestor->votar($votacion, $usuario, $request->string('opcion')->toString());

        return response()->json(['ok' => true]);
    }

    public function documento(Banca $banca, Documento $documento, AlmacenamientoDocumentos $almacenamiento): StreamedResponse
    {
        $this->autorizar($banca);
        $estado = EstadoRecinto::query()->where('documento_mostrado_id', $documento->id)->whereHas('sesionParlamentaria', fn ($q) => $q->where('estado', 'en_curso'))->exists();
        abort_unless($estado, 404);

        return $almacenamiento->visualizar($documento->ruta, $documento->nombre_archivo);
    }

    private function autorizar(Banca $banca): User
    {
        $usuarioBanca = $banca->usuario;
        abort_unless($usuarioBanca && (request()->user()->is($usuarioBanca) || request()->user()->puedeConfigurarRecinto()), 403);

        return $usuarioBanca;
    }
}
