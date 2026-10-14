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

    public function test_el_heartbeat_mantiene_la_sesion_y_renueva_csrf_mientras_hay_sesion_parlamentaria(): void
    {
        app(GestorRecinto::class)->activarSesion($this->sesion);
        $tokenAnterior = 'token-anterior';

        $respuesta = $this->actingAs($this->secretaria)->withSession(['_token' => $tokenAnterior])->getJson(route('latido'));

        $respuesta->assertOk()->assertJson(['activa' => true]);
        $this->assertAuthenticatedAs($this->secretaria);
        $this->assertNotSame($tokenAnterior, $respuesta->json('csrf_token'));
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
            ->assertJsonPath('habilitada', true)->assertJsonPath('documento.titulo', $documento->titulo);
    }

    public function test_voto_directo_puede_corregirse_y_cierra_cuando_votan_todos_los_presentes(): void
    {
        $primero = $this->legislador(1);
        $segundo = $this->legislador(2);
        $gestor = app(GestorRecinto::class);
        $gestor->activarSesion($this->sesion);
        $gestor->registrarAsistencia($this->sesion, $primero, 'presente', $this->secretaria);
        $gestor->registrarAsistencia($this->sesion, $segundo, 'presente', $this->secretaria);
        $votacion = Votacion::factory()->create(['sesion_parlamentaria_id' => $this->sesion->id, 'metodo' => 'directo', 'estado' => 'configurada']);
        $gestor->iniciarVotacion($votacion, 60);

        $this->actingAs($primero)->putJson(route('bancas.votar', [$primero->banca, $votacion]), ['opcion' => 'negativo'])->assertOk();
        $this->actingAs($primero)->putJson(route('bancas.votar', [$primero->banca, $votacion]), ['opcion' => 'afirmativo'])->assertOk();
        $this->assertSame('abierta', $votacion->fresh()->estado);
        $this->actingAs($segundo)->putJson(route('bancas.votar', [$segundo->banca, $votacion]), ['opcion' => 'abstencion'])->assertOk();

        $this->assertSame('cerrada', $votacion->fresh()->estado);
        $this->assertTrue($votacion->fresh()->resultado_publicado);
        $this->assertDatabaseHas('votos', ['votacion_id' => $votacion->id, 'usuario_id' => $primero->id, 'opcion' => 'afirmativo']);
        $this->getJson(route('monitor.estado'))->assertJsonPath('resultado.afirmativos', 1)->assertJsonPath('resultado.abstenciones', 1);
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
