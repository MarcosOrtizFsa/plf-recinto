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
        $ultima = $sesion->votaciones()->where('estado', 'cerrada')->where('resultado_publicado', true)->latest('cerrada_en')->withCount([
            'votos as afirmativos' => fn ($q) => $q->where('opcion', 'afirmativo'),
            'votos as negativos' => fn ($q) => $q->where('opcion', 'negativo'),
            'votos as abstenciones' => fn ($q) => $q->where('opcion', 'abstencion'),
        ])->first();

        return [
            'activa' => true, 'version' => $estado->version, 'vista' => $estado->vista_monitor,
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
            'votacion' => $estado->votacionActiva ? $this->datosVotacion($estado->votacionActiva, false) : null,
            'resultado' => $ultima ? ['tema' => $ultima->tema, 'afirmativos' => $ultima->afirmativos, 'negativos' => $ultima->negativos, 'abstenciones' => $ultima->abstenciones] : null,
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
        $legisladores = User::query()->whereHas('rol', fn ($q) => $q->where('slug', 'legisladores'))->with('banca')->orderBy('nombre')->get()->map(fn (User $u) => [
            'id' => $u->id, 'nombre' => $u->nombre, 'banca' => $u->banca?->numero, 'banca_id' => $u->banca_id,
            'banca_activa' => (bool) $u->banca?->activa,
            'asistencia' => $asistencias->get($u->id)?->registrada_en ? $asistencias->get($u->id)?->estado : 'sin_marcar',
        ]);

        return [...$publico, 'legisladores' => $legisladores, 'documento' => $estado->documentoMostrado ? ['id' => $estado->documentoMostrado->id, 'titulo' => $estado->documentoMostrado->titulo] : null];
    }

    public function banca(Banca $banca, User $usuario): array
    {
        $this->gestor->cerrarVencidas();
        $estado = $this->estado();
        if (! $estado) {
            return ['activa' => false, 'version' => 0, 'legislador' => $usuario->nombre, 'banca' => $banca->numero];
        }
        $asistencia = $estado->sesionParlamentaria->asistencias()->where('usuario_id', $usuario->id)->value('estado') ?? 'ausente';
        $presente = $asistencia === 'presente';
        $votacion = $estado->votacionActiva;
        $voto = $votacion?->votos()->where('usuario_id', $usuario->id)->value('opcion');

        return [
            'activa' => true, 'version' => $estado->version, 'legislador' => $usuario->nombre, 'banca' => $banca->numero,
            'habilitada' => $presente && $banca->activa, 'asistencia' => $asistencia, 'motivo_anulacion' => $banca->motivo_anulacion,
            'documento' => $estado->documentoMostrado ? ['titulo' => $estado->documentoMostrado->titulo, 'url' => route('bancas.documento', [$banca, $estado->documentoMostrado])] : null,
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
