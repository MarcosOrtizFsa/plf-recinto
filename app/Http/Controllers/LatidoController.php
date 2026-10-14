<?php

namespace App\Http\Controllers;

use App\Models\SesionParlamentaria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LatidoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $activa = SesionParlamentaria::query()->where('estado', 'en_curso')->exists();
        if ($activa) {
            $request->session()->put('ultimo_latido_recinto', now()->timestamp);
            $request->session()->regenerateToken();
        }

        return response()->json(['activa' => $activa, 'csrf_token' => csrf_token(), 'intervalo_segundos' => config('recinto.sesion_web.latido_segundos')]);
    }
}
