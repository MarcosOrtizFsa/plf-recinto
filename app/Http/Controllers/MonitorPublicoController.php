<?php

namespace App\Http\Controllers;

use App\Services\Recinto\ConsultaEstadoRecinto;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MonitorPublicoController extends Controller
{
    public function show(): View
    {
        return view('monitor.show');
    }

    public function estado(ConsultaEstadoRecinto $consulta): JsonResponse
    {
        return response()->json($consulta->publico());
    }
}
