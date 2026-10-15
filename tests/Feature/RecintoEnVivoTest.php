<?php

namespace Tests\Feature;

use App\Events\EstadoRecintoActualizado;
use App\Models\Banca;
use App\Models\Documento;
use App\Models\Rol;
use App\Models\SesionParlamentaria;
use App\Models\User;
use App\Models\Votacion;
use App\Services\Recinto\GestorRecinto;
use Database\Seeders\BancasSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RecintoEnVivoTest extends TestCase
{
    use RefreshDatabase;

    protected User $secretaria;

    protected SesionParlamentaria $sesion;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([EstadoRecintoActualizado::class]);
        $this->seed([RolesSeeder::class, BancasSeeder::class]);
        $this->secretaria = User::factory()->create(['rol_id' => Rol::where('slug', 'secretaria')->value('id')]);
        $this->sesion = SesionParlamentaria::factory()->create(['estado' => 'borrador']);
    }

    public function test_el_heartbeat_mantiene_la_sesion_y_conserva_un_csrf_valido_mientras_hay_sesion_parlamentaria(): void
    {
        app(GestorRecinto::class)->activarSesion($this->sesion);
        $tokenAnterior = 'token-anterior';

        $respuesta = $this->actingAs($this->secretaria)->withSession(['_token' => $tokenAnterior])->getJson(route('latido'));

        $respuesta->assertOk()->assertJson(['activa' => true]);
        $this->assertAuthenticatedAs($this->secretaria);
        $this->assertSame($tokenAnterior, $respuesta->json('csrf_token'));
        $respuesta->assertSessionHas('ultimo_latido_recinto');
        $this->assertSame(1560, config('session.lifetime'));

        app(GestorRecinto::class)->cerrarSesion($this->sesion->fresh());
        $this->getJson(route('latido'))->assertJson(['activa' => false]);
        $this->assertSame(120, config('session.lifetime'));
    }

    public function test_secretaria_accede_a_comando_y_el_monitor_es_publico(): void
    {
        $this->actingAs($this->secretaria)->get(route('comando.index'))->assertOk()->assertSee('Comando');
        auth()->logout();
        $this->get(route('monitor.show'))->assertOk()->assertSee('Monitor público');
    }

    public function test_asistencia_habilita_la_banca_y_el_documento_se_resincroniza_desde_mysql(): void
    {
        $legislador = $this->legislador(1);
        $documento = Documento::factory()->create();
        $this->sesion->documentos()->attach($documento, ['activo' => true, 'orden' => 1]);
        $gestor = app(GestorRecinto::class);
        $gestor->activarSesion($this->sesion);
        $gestor->registrarAsistencia($this->sesion, $legislador, 'presente', $this->secretaria);
        $gestor->mostrarDocumento($this->sesion, $documento);

        $this->actingAs($legislador)->getJson(route('bancas.estado', $legislador->banca))->assertOk()
            ->assertJsonPath('habilitada', true)
            ->assertJsonPath('asistencia', 'presente')
            ->assertJsonPath('sesion.nombre', $this->sesion->nombre)
            ->assertJsonPath('documento.titulo', $documento->titulo);

        $this->actingAs($legislador)->get(route('bancas.show', $legislador->banca))
            ->assertOk()
            ->assertSee($legislador->nombre)
            ->assertDontSee('Administrador Root')
            ->assertDontSee('Configuración');
    }

    public function test_monitor_publico_distingue_bancas_sin_marcar_presentes_y_ausentes(): void
    {
        $primero = $this->legislador(1);
        $segundo = $this->legislador(2);
        $gestor = app(GestorRecinto::class);
        $gestor->activarSesion($this->sesion);

        $this->getJson(route('monitor.estado'))->assertOk()
            ->assertJsonPath('asistencia.total', 2)
            ->assertJsonPath('asistencia.sin_marcar', 2)
            ->assertJsonPath('asistencia.ausentes', 0);

        $gestor->registrarAsistencia($this->sesion, $primero, 'presente', $this->secretaria);
        $gestor->registrarAsistencia($this->sesion, $segundo, 'ausente', $this->secretaria);

        $this->getJson(route('monitor.estado'))->assertOk()
            ->assertJsonPath('asistencia.cantidad', 1)
            ->assertJsonPath('asistencia.ausentes', 1)
            ->assertJsonPath('asistencia.sin_marcar', 0)
            ->assertJsonPath('asistencia.porcentaje', 50)
            ->assertJsonPath('asistencia.quorum', false)
            ->assertJsonPath('asistencia.legisladores.0.asistencia', 'presente')
            ->assertJsonPath('asistencia.legisladores.1.asistencia', 'ausente');
    }

    public function test_comando_actualiza_y_reinicia_asistencia_por_ajax_sin_recargar(): void
    {
        $legislador = $this->legislador(1);
        $gestor = app(GestorRecinto::class);
        $gestor->activarSesion($this->sesion);

        $this->actingAs($this->secretaria)->putJson(route('comando.asistencia', $this->sesion), [
            'usuario_id' => $legislador->id,
            'estado' => 'presente',
        ])->assertOk()->assertJson(['estado' => 'Asistencia actualizada.']);

        $this->getJson(route('monitor.estado'))->assertJsonPath('asistencia.cantidad', 1);

        $this->putJson(route('comando.asistencia', $this->sesion), [
            'usuario_id' => $legislador->id,
            'estado' => 'sin_marcar',
        ])->assertOk();

        $this->getJson(route('monitor.estado'))
            ->assertJsonPath('asistencia.cantidad', 0)
            ->assertJsonPath('asistencia.sin_marcar', 1);

        $this->putJson(route('comando.asistencia', $this->sesion), [
            'usuario_id' => $legislador->id,
            'estado' => 'ausente',
        ])->assertOk();

        $this->putJson(route('comando.asistencia.reset', $this->sesion))->assertOk()
            ->assertJson(['estado' => 'Asistencia reiniciada.']);
        $this->getJson(route('monitor.estado'))
            ->assertJsonPath('asistencia.ausentes', 0)
            ->assertJsonPath('asistencia.sin_marcar', 1);
    }

    public function test_comando_suspende_y_reanuda_el_recinto_sin_finalizar_la_sesion(): void
    {
        $legislador = $this->legislador(1);
        app(GestorRecinto::class)->activarSesion($this->sesion);

        $this->actingAs($this->secretaria)
            ->post(route('comando.sesiones.suspender', $this->sesion))
            ->assertRedirect();

        $this->assertSame('en_curso', $this->sesion->fresh()->estado);
        $this->assertTrue((bool) $this->sesion->estadoRecinto->fresh()->configuracion['suspendida']);
        $this->getJson(route('monitor.estado'))
            ->assertJsonPath('activa', false)
            ->assertJsonPath('suspendida', true);
        $this->actingAs($legislador)->getJson(route('bancas.estado', $legislador->banca))
            ->assertJsonPath('activa', false);
        $this->getJson(route('latido'))->assertJsonPath('activa', true);

        $this->actingAs($this->secretaria)
            ->post(route('comando.sesiones.reanudar', $this->sesion))
            ->assertRedirect();

        $this->assertSame('en_curso', $this->sesion->fresh()->estado);
        $this->getJson(route('monitor.estado'))
            ->assertJsonPath('activa', true)
            ->assertJsonPath('suspendida', false);
    }

    public function test_voto_directo_puede_corregirse_y_solo_se_publica_desde_comando(): void
    {
        $primero = $this->legislador(1);
        $segundo = $this->legislador(2);
        $gestor = app(GestorRecinto::class);
        $gestor->activarSesion($this->sesion);
        $gestor->registrarAsistencia($this->sesion, $primero, 'presente', $this->secretaria);
        $gestor->registrarAsistencia($this->sesion, $segundo, 'presente', $this->secretaria);
        $documento = Documento::factory()->create();
        $this->sesion->documentos()->attach($documento, ['activo' => true, 'orden' => 1]);
        $gestor->mostrarDocumento($this->sesion, $documento);
        $votacion = Votacion::factory()->create(['sesion_parlamentaria_id' => $this->sesion->id, 'metodo' => 'directo', 'estado' => 'configurada']);
        $gestor->iniciarVotacion($votacion, 60, 'directo');
        $this->actingAs($primero)->getJson(route('bancas.estado', $primero->banca))
            ->assertJsonPath('documento', null)
            ->assertJsonPath('votacion.id', $votacion->id);
        $this->actingAs($this->secretaria)->postJson(route('comando.votaciones.visibilidad', $votacion), ['visible' => true])->assertOk();
        $this->getJson(route('monitor.estado'))
            ->assertJsonPath('vista', 'votacion')
            ->assertJsonPath('votacion_en_vivo', true)
            ->assertJsonPath('votacion.bancas.0.voto', null);

        $this->actingAs($primero)->putJson(route('bancas.votar', [$primero->banca, $votacion]), ['opcion' => 'negativo'])->assertOk();
        $this->actingAs($primero)->putJson(route('bancas.votar', [$primero->banca, $votacion]), ['opcion' => 'afirmativo'])->assertOk();
        $this->assertSame('abierta', $votacion->fresh()->estado);
        $this->actingAs($segundo)->putJson(route('bancas.votar', [$segundo->banca, $votacion]), ['opcion' => 'abstencion'])->assertOk();
        $this->getJson(route('monitor.estado'))
            ->assertJsonPath('votacion.afirmativos', 1)
            ->assertJsonPath('votacion.abstenciones', 1);

        $this->assertSame('abierta', $votacion->fresh()->estado);
        $this->assertDatabaseHas('votos', ['votacion_id' => $votacion->id, 'usuario_id' => $primero->id, 'opcion' => 'afirmativo']);

        $gestor->cerrarVotacion($votacion->fresh());
        $this->assertSame('cerrada', $votacion->fresh()->estado);
        $this->assertFalse($votacion->fresh()->resultado_publicado);
        $this->getJson(route('monitor.estado'))->assertJsonPath('resultado', null);

        $gestor->publicarResultado($votacion->fresh());
        $this->assertTrue($votacion->fresh()->resultado_publicado);
        $this->getJson(route('monitor.estado'))->assertJsonPath('resultado.afirmativos', 1)->assertJsonPath('resultado.abstenciones', 1)->assertJsonPath('resultado.bancas.0.voto', 'afirmativo');

        $this->actingAs($this->secretaria)->putJson(route('comando.votaciones.reiniciar', $votacion))->assertOk();
        $votacion->refresh();
        $this->assertSame('configurada', $votacion->estado);
        $this->assertNull($votacion->iniciada_en);
        $this->assertNull($votacion->finaliza_en);
        $this->assertNull($votacion->cerrada_en);
        $this->assertFalse($votacion->resultado_publicado);
        $this->assertSame(0, $votacion->votos()->count());
        $this->getJson(route('monitor.estado'))
            ->assertJsonPath('vista', 'asistencia')
            ->assertJsonPath('resultado', null);
    }

    public function test_secretaria_registra_voto_a_viva_voz_y_publica_el_resultado(): void
    {
        $legislador = $this->legislador(1);
        $gestor = app(GestorRecinto::class);
        $gestor->activarSesion($this->sesion);
        $gestor->registrarAsistencia($this->sesion, $legislador, 'presente', $this->secretaria);
        $votacion = Votacion::factory()->create(['sesion_parlamentaria_id' => $this->sesion->id, 'estado' => 'configurada']);

        $this->actingAs($this->secretaria)->postJson(route('comando.votaciones.iniciar', $votacion), [
            'metodo' => 'asistido',
            'duracion_segundos' => 60,
        ])->assertOk();

        $this->putJson(route('comando.votaciones.voto', $votacion), [
            'usuario_id' => $legislador->id,
            'opcion' => 'afirmativo',
        ])->assertOk();
        $this->assertDatabaseHas('votos', [
            'votacion_id' => $votacion->id,
            'usuario_id' => $legislador->id,
            'opcion' => 'afirmativo',
            'registrado_por' => $this->secretaria->id,
        ]);

        $this->postJson(route('comando.votaciones.cerrar', $votacion))->assertOk();
        $this->assertFalse($votacion->fresh()->resultado_publicado);
        $this->postJson(route('comando.votaciones.publicar', $votacion))->assertOk();
        $this->assertTrue($votacion->fresh()->resultado_publicado);

        $this->postJson(route('comando.votaciones.despublicar', $votacion))->assertOk();
        $this->assertFalse($votacion->fresh()->resultado_publicado);
        $this->getJson(route('monitor.estado'))
            ->assertJsonPath('vista', 'asistencia')
            ->assertJsonPath('resultado', null);
    }

    public function test_el_comando_programado_cierra_votaciones_vencidas(): void
    {
        $gestor = app(GestorRecinto::class);
        $gestor->activarSesion($this->sesion);
        $votacion = Votacion::factory()->create(['sesion_parlamentaria_id' => $this->sesion->id, 'metodo' => 'directo', 'estado' => 'abierta', 'iniciada_en' => now()->subMinute(), 'finaliza_en' => now()->subSecond()]);
        $this->sesion->estadoRecinto()->update(['votacion_activa_id' => $votacion->id, 'vista_monitor' => 'votacion']);

        $this->artisan('votaciones:cerrar-vencidas')->assertSuccessful();
        $this->assertSame('cerrada', $votacion->fresh()->estado);
    }

    private function legislador(int $numeroBanca): User
    {
        return User::factory()->create(['rol_id' => Rol::where('slug', 'legisladores')->value('id'), 'banca_id' => Banca::where('numero', $numeroBanca)->value('id')]);
    }
}
