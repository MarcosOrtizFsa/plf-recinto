<?php

namespace App\Services\Recinto;

use App\Events\EstadoRecintoActualizado;
use App\Models\Asistencia;
use App\Models\Banca;
use App\Models\Documento;
use App\Models\EstadoRecinto;
use App\Models\SesionParlamentaria;
use App\Models\User;
use App\Models\Votacion;
use App\Models\Voto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class GestorRecinto
{
    public function activarSesion(SesionParlamentaria $sesion): EstadoRecinto
    {
        return DB::transaction(function () use ($sesion) {
            if (SesionParlamentaria::query()->where('estado', 'en_curso')->whereKeyNot($sesion->id)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['sesion' => 'Ya existe otra sesión parlamentaria activa.']);
            }

            $sesion->update(['estado' => 'en_curso', 'iniciada_en' => $sesion->iniciada_en ?? now(), 'finalizada_en' => null]);
            User::query()->whereHas('rol', fn ($q) => $q->where('slug', 'legisladores'))->each(fn (User $usuario) => Asistencia::query()->firstOrCreate(
                ['sesion_parlamentaria_id' => $sesion->id, 'usuario_id' => $usuario->id],
                ['estado' => 'ausente'],
            ));
            $estado = EstadoRecinto::query()->updateOrCreate(
                ['sesion_parlamentaria_id' => $sesion->id],
                ['vista_monitor' => 'asistencia', 'documento_mostrado_id' => null, 'votacion_activa_id' => null],
            );

            return $this->tocar($estado, 'sesion.iniciada');
        });
    }

    public function cerrarSesion(SesionParlamentaria $sesion): void
    {
        DB::transaction(function () use ($sesion) {
            $estado = $this->estadoDe($sesion);
            if ($estado->votacionActiva) {
                $this->cerrarVotacionInterna($estado->votacionActiva, $estado);
            }
            $sesion->update(['estado' => 'finalizada', 'finalizada_en' => now()]);
            $estado->update(['vista_monitor' => 'espera', 'documento_mostrado_id' => null, 'votacion_activa_id' => null]);
            $this->tocar($estado, 'sesion.finalizada');
        });
    }

    public function registrarAsistencia(SesionParlamentaria $sesion, User $legislador, string $estadoAsistencia, User $operador): void
    {
        throw_unless($legislador->tieneRol('legisladores') && $legislador->banca_id, ValidationException::withMessages(['usuario_id' => 'La asistencia sólo corresponde a legisladores con banca.']));

        DB::transaction(function () use ($sesion, $legislador, $estadoAsistencia, $operador) {
            Asistencia::query()->updateOrCreate(
                ['sesion_parlamentaria_id' => $sesion->id, 'usuario_id' => $legislador->id],
                ['estado' => $estadoAsistencia, 'registrada_en' => now(), 'registrada_por' => $operador->id],
            );
            $this->tocar($this->estadoDe($sesion), 'asistencia.actualizada');
        });
    }

    public function cambiarBanca(SesionParlamentaria $sesion, Banca $banca, bool $activa, ?string $motivo): void
    {
        DB::transaction(function () use ($sesion, $banca, $activa, $motivo) {
            $banca->update(['activa' => $activa, 'motivo_anulacion' => $activa ? null : $motivo, 'anulada_en' => $activa ? null : now()]);
            $this->tocar($this->estadoDe($sesion), 'banca.actualizada');
        });
    }

    public function mostrarDocumento(SesionParlamentaria $sesion, Documento $documento): void
    {
        $disponible = $sesion->documentos()->whereKey($documento->id)->wherePivot('activo', true)->exists() && $documento->activo;
        throw_unless($disponible, ValidationException::withMessages(['documento' => 'El documento no está activo para esta sesión.']));
        DB::transaction(fn () => $this->tocar(tap($this->estadoDe($sesion))->update(['documento_mostrado_id' => $documento->id, 'vista_monitor' => 'documento']), 'documento.mostrado'));
    }

    public function iniciarVotacion(Votacion $votacion, int $duracion): void
    {
        DB::transaction(function () use ($votacion, $duracion) {
            $estado = $this->estadoDe($votacion->sesionParlamentaria);
            if ($estado->votacion_activa_id) {
                throw ValidationException::withMessages(['votacion' => 'Ya existe una votación activa.']);
            }
            $votacion->update(['estado' => 'abierta', 'duracion_segundos' => $duracion, 'iniciada_en' => now(), 'finaliza_en' => now()->addSeconds($duracion), 'cerrada_en' => null, 'resultado_publicado' => false]);
            $estado->update(['votacion_activa_id' => $votacion->id, 'vista_monitor' => 'votacion']);
            $this->tocar($estado, 'votacion.iniciada');
        });
    }

    public function votar(Votacion $votacion, User $legislador, string $opcion, ?User $registrador = null): void
    {
        DB::transaction(function () use ($votacion, $legislador, $opcion, $registrador) {
            $votacion->refresh();
            if ($votacion->estado !== 'abierta' || now()->greaterThanOrEqualTo($votacion->finaliza_en)) {
                throw ValidationException::withMessages(['voto' => 'La votación ya finalizó.']);
            }
            $habilitado = Asistencia::query()->where('sesion_parlamentaria_id', $votacion->sesion_parlamentaria_id)->where('usuario_id', $legislador->id)->where('estado', 'presente')->exists() && $legislador->banca?->activa;
            throw_unless($habilitado, ValidationException::withMessages(['voto' => 'La banca no está habilitada para votar.']));

            Voto::query()->updateOrCreate(
                ['votacion_id' => $votacion->id, 'usuario_id' => $legislador->id],
                ['banca_id' => $legislador->banca_id, 'opcion' => $opcion, 'registrado_por' => $registrador?->id, 'emitido_en' => now()],
            );
            $estado = $this->estadoDe($votacion->sesionParlamentaria);
            $this->tocar($estado, 'voto.registrado');

            if ($votacion->votos()->count() >= $this->cantidadHabilitados($votacion->sesionParlamentaria)) {
                $this->cerrarVotacionInterna($votacion, $estado);
            }
        });
    }

    public function cerrarVotacion(Votacion $votacion): void
    {
        DB::transaction(fn () => $this->cerrarVotacionInterna($votacion, $this->estadoDe($votacion->sesionParlamentaria)));
    }

    public function cerrarVencidas(): int
    {
        $cantidad = 0;
        Votacion::query()->where('estado', 'abierta')->where('finaliza_en', '<=', now())->each(function (Votacion $votacion) use (&$cantidad) {
            $this->cerrarVotacion($votacion);
            $cantidad++;
        });

        return $cantidad;
    }

    private function cerrarVotacionInterna(Votacion $votacion, EstadoRecinto $estado): void
    {
        if ($votacion->estado !== 'abierta') {
            return;
        }
        $votacion->update(['estado' => 'cerrada', 'cerrada_en' => now(), 'resultado_publicado' => true]);
        $estado->update(['votacion_activa_id' => null, 'vista_monitor' => 'resultado']);
        $this->tocar($estado, 'votacion.finalizada');
    }

    private function cantidadHabilitados(SesionParlamentaria $sesion): int
    {
        return Asistencia::query()->where('sesion_parlamentaria_id', $sesion->id)->where('estado', 'presente')->whereHas('usuario.banca', fn ($q) => $q->where('activa', true))->count();
    }

    private function estadoDe(SesionParlamentaria $sesion): EstadoRecinto
    {
        return EstadoRecinto::query()->where('sesion_parlamentaria_id', $sesion->id)->firstOrFail();
    }

    private function tocar(EstadoRecinto $estado, string $motivo): EstadoRecinto
    {
        $estado->increment('version');
        $estado->refresh();
        DB::afterCommit(function () use ($estado, $motivo) {
            try {
                event(new EstadoRecintoActualizado($estado->version, $motivo));
            } catch (Throwable) {
                // El WebSocket sólo avisa; una caída de Reverb nunca revierte MySQL.
            }
        });

        return $estado;
    }
}
