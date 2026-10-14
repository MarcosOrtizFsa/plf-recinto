<?php

namespace App\Http\Middleware;

use App\Models\SesionParlamentaria;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ConfigurarSesionRecinto
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        config(['session.lifetime' => config('recinto.sesion_web.normal_minutos')]);

        if ($request->is('comando*', 'bancas*', 'monitor*', 'latido') && $this->ipPermitida($request)) {
            try {
                if (SesionParlamentaria::query()->where('estado', 'en_curso')->exists()) {
                    config(['session.lifetime' => config('recinto.sesion_web.recinto_minutos')]);
                }
            } catch (Throwable) {
                // Una caída de DB no debe convertir la petición en un error de sesión.
            }
        }

        return $next($request);
    }

    private function ipPermitida(Request $request): bool
    {
        $permitidas = config('recinto.sesion_web.ips_permitidas');

        return $permitidas === [] || in_array($request->ip(), $permitidas, true);
    }
}
