<?php

namespace App\Services\Recinto;

use App\Models\Banca;
use App\Models\EstadoRecinto;
use App\Models\User;

class ConsultaEstadoRecinto
{
    public function __construct(private readonly GestorRecinto $gestor) {}

    public function publico(): array
    {
        $this->gestor->cerrarVencidas();
        $estado = $this->estado();
        if (! $estado) {
            return ['activa' => false, 'vista' => 'espera', 'version' => 0];
        }
        $sesion = $estado->sesionParlamentaria;
        if (($estado->configuracion['suspendida'] ?? false) === true) {
            return [
                'activa' => false,
                'suspendida' => true,
                'vista' => 'espera',
                'version' => $estado->version,
                'sesion' => ['id' => $sesion->id, 'nombre' => $sesion->nombre, 'numero' => $sesion->numero],
            ];
        }
        $asistencias = $sesion->asistencias()->get()->keyBy('usuario_id');
        $legisladores = User::query()
            ->whereHas('rol', fn ($q) => $q->where('slug', 'legisladores'))
            ->whereNotNull('banca_id')
            ->with('banca:id,numero')
            ->get()
            ->sortBy(fn (User $usuario) => $usuario->banca?->numero)
            ->values()
            ->map(function (User $usuario) use ($asistencias) {
                $asistencia = $asistencias->get($usuario->id);

                return [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'banca' => $usuario->banca?->numero,
                    'asistencia' => $asistencia?->registrada_en ? $asistencia->estado : 'sin_marcar',
                ];
            });
        $presentes = $legisladores->where('asistencia', 'presente')->values();
        $ausentes = $legisladores->where('asistencia', 'ausente')->values();
        $justificados = $legisladores->where('asistencia', 'justificado')->values();
        $total = $legisladores->count();
        $porcentaje = $total > 0 ? round(($presentes->count() / $total) * 100, 1) : 0;
        $votacionEnVivo = ($estado->configuracion['votacion_en_vivo'] ?? false) === true;
        $votacionPublica = null;
        if ($estado->votacionActiva) {
            $votacionPublica = $this->datosVotacion($estado->votacionActiva, false);
            if ($votacionEnVivo) {
                $votos = $estado->votacionActiva->votos()->pluck('opcion', 'usuario_id');
                $votacionPublica['bancas'] = $legisladores->map(fn (array $legislador) => [
                    ...$legislador,
                    'voto' => $legislador['asistencia'] === 'presente' ? $votos->get($legislador['id']) : null,
                ]);
                $votacionPublica['afirmativos'] = $votos->filter(fn ($voto) => $voto === 'afirmativo')->count();
                $votacionPublica['negativos'] = $votos->filter(fn ($voto) => $voto === 'negativo')->count();
                $votacionPublica['abstenciones'] = $votos->filter(fn ($voto) => $voto === 'abstencion')->count();
            }
        }
        $ultima = $sesion->votaciones()->where('estado', 'cerrada')->where('resultado_publicado', true)->latest('cerrada_en')->with('votos')->withCount([
            'votos as afirmativos' => fn ($q) => $q->where('opcion', 'afirmativo'),
            'votos as negativos' => fn ($q) => $q->where('opcion', 'negativo'),
            'votos as abstenciones' => fn ($q) => $q->where('opcion', 'abstencion'),
        ])->first();

        return [
            'activa' => true, 'suspendida' => false, 'version' => $estado->version, 'vista' => $estado->vista_monitor,
            'sesion' => ['id' => $sesion->id, 'nombre' => $sesion->nombre, 'numero' => $sesion->numero],
            'asistencia' => [
                'cantidad' => $presentes->count(),
                'presentes' => $presentes->pluck('nombre'),
                'ausentes' => $ausentes->count(),
                'justificados' => $justificados->count(),
                'sin_marcar' => $legisladores->where('asistencia', 'sin_marcar')->count(),
                'total' => $total,
                'porcentaje' => $porcentaje,
                'quorum' => $porcentaje >= 60,
                'legisladores' => $legisladores,
            ],
            'votacion_en_vivo' => $votacionEnVivo,
            'votacion' => $votacionPublica,
            'resultado' => $ultima ? [
                'tema' => $ultima->tema,
                'afirmativos' => $ultima->afirmativos,
                'negativos' => $ultima->negativos,
                'abstenciones' => $ultima->abstenciones,
                'bancas' => $legisladores->map(function (array $legislador) use ($ultima) {
                    $voto = $ultima->votos->firstWhere('usuario_id', $legislador['id']);

                    return [...$legislador, 'voto' => $legislador['asistencia'] === 'presente' ? $voto?->opcion : null];
                }),
            ] : null,
        ];
    }

    public function comando(): array
    {
        $publico = $this->publico();
        $estado = $this->estado();
        if (! $estado) {
            return $publico;
        }
        $sesion = $estado->sesionParlamentaria;
        $asistencias = $sesion->asistencias()->with(['usuario.rol', 'usuario.banca'])->get()->keyBy('usuario_id');
        $votosActivos = $estado->votacionActiva?->votos()->pluck('opcion', 'usuario_id') ?? collect();
        $legisladores = User::query()->whereHas('rol', fn ($q) => $q->where('slug', 'legisladores'))->with('banca')->orderBy('nombre')->get()->map(fn (User $u) => [
            'id' => $u->id, 'nombre' => $u->nombre, 'banca' => $u->banca?->numero, 'banca_id' => $u->banca_id,
            'banca_activa' => (bool) $u->banca?->activa,
            'asistencia' => $asistencias->get($u->id)?->registrada_en ? $asistencias->get($u->id)?->estado : 'sin_marcar',
            'voto' => $votosActivos->get($u->id),
        ]);
        $votaciones = $sesion->votaciones()->withCount([
            'votos',
            'votos as afirmativos' => fn ($q) => $q->where('opcion', 'afirmativo'),
            'votos as negativos' => fn ($q) => $q->where('opcion', 'negativo'),
            'votos as abstenciones' => fn ($q) => $q->where('opcion', 'abstencion'),
        ])->latest()->get()->map(fn ($votacion) => [
            'id' => $votacion->id,
            'tema' => $votacion->tema,
            'tipo' => $votacion->tipo,
            'cantidad_articulos' => $votacion->cantidad_articulos,
            'metodo' => $votacion->metodo,
            'estado' => $votacion->estado,
            'duracion_segundos' => $votacion->duracion_segundos ?? 60,
            'finaliza_en' => $votacion->finaliza_en?->toIso8601String(),
            'resultado_publicado' => $votacion->resultado_publicado,
            'votos' => $votacion->votos_count,
            'afirmativos' => $votacion->afirmativos,
            'negativos' => $votacion->negativos,
            'abstenciones' => $votacion->abstenciones,
        ]);

        return [...$publico, 'legisladores' => $legisladores, 'votacion_en_vivo' => ($estado->configuracion['votacion_en_vivo'] ?? false) === true, 'votaciones_comando' => $votaciones, 'documento' => $estado->documentoMostrado ? [
            'id' => $estado->documentoMostrado->id,
            'titulo' => $estado->documentoMostrado->titulo,
            'preview_url' => route('configuracion.documentos.preview', $estado->documentoMostrado),
        ] : null];
    }

    public function banca(Banca $banca, User $usuario): array
    {
        $this->gestor->cerrarVencidas();
        $estado = $this->estado();
        if (! $estado || ($estado->configuracion['suspendida'] ?? false) === true) {
            return [
                'activa' => false,
                'version' => $estado?->version ?? 0,
                'legislador' => $usuario->nombre,
                'banca' => $banca->numero,
                'sesion' => $estado ? ['nombre' => $estado->sesionParlamentaria->nombre, 'numero' => $estado->sesionParlamentaria->numero] : null,
            ];
        }
        $asistencia = $estado->sesionParlamentaria->asistencias()->where('usuario_id', $usuario->id)->value('estado') ?? 'ausente';
        $presente = $asistencia === 'presente';
        $votacion = $estado->votacionActiva;
        $voto = $votacion?->votos()->where('usuario_id', $usuario->id)->value('opcion');

        return [
            'activa' => true, 'version' => $estado->version, 'legislador' => $usuario->nombre, 'banca' => $banca->numero,
            'sesion' => ['nombre' => $estado->sesionParlamentaria->nombre, 'numero' => $estado->sesionParlamentaria->numero],
            'habilitada' => $presente && $banca->activa, 'asistencia' => $asistencia, 'motivo_anulacion' => $banca->motivo_anulacion,
            'documento' => ! $votacion && $estado->documentoMostrado ? ['titulo' => $estado->documentoMostrado->titulo, 'url' => route('bancas.documento', [$banca, $estado->documentoMostrado])] : null,
            'votacion' => $votacion && $votacion->metodo === 'directo' ? [...$this->datosVotacion($votacion, false), 'voto' => $voto] : null,
        ];
    }

    private function datosVotacion($votacion, bool $resultados): array
    {
        return ['id' => $votacion->id, 'tema' => $votacion->tema, 'metodo' => $votacion->metodo, 'estado' => $votacion->estado, 'finaliza_en' => $votacion->finaliza_en?->toIso8601String()];
    }

    private function estado(): ?EstadoRecinto
    {
        return EstadoRecinto::query()->whereHas('sesionParlamentaria', fn ($q) => $q->where('estado', 'en_curso'))->with(['sesionParlamentaria', 'documentoMostrado', 'votacionActiva'])->first();
    }
}
