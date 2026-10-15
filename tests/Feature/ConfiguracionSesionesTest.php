<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\SesionParlamentaria;
use App\Models\User;
use App\Services\Recinto\GestorRecinto;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracionSesionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_crea_y_actualiza_una_sesion_en_borrador(): void
    {
        $this->seed(RolesSeeder::class);
        $root = User::factory()->create(['rol_id' => Rol::where('slug', 'root')->value('id')]);

        $this->actingAs($root)->post(route('configuracion.sesiones.store'), ['nombre' => 'Sesión Ordinaria', 'numero' => '12/2026', 'fecha_inicio_programada' => '2026-10-20 10:00'])->assertRedirect();
        $sesion = SesionParlamentaria::firstOrFail();
        $this->assertSame('borrador', $sesion->estado);

        $this->actingAs($root)->put(route('configuracion.sesiones.update', $sesion), ['nombre' => 'Sesión Ordinaria Actualizada', 'numero' => '12/2026'])->assertRedirect();
        $this->assertDatabaseHas('sesiones_parlamentarias', ['id' => $sesion->id, 'nombre' => 'Sesión Ordinaria Actualizada']);
    }

    public function test_finaliza_y_reactiva_una_sesion_desde_configuracion(): void
    {
        $this->seed(RolesSeeder::class);
        $root = User::factory()->create(['rol_id' => Rol::where('slug', 'root')->value('id')]);
        $sesion = SesionParlamentaria::factory()->create(['estado' => 'borrador']);
        app(GestorRecinto::class)->activarSesion($sesion);

        $this->actingAs($root)->post(route('configuracion.sesiones.finalizar', $sesion))->assertRedirect();
        $this->assertSame('finalizada', $sesion->fresh()->estado);
        $this->assertNotNull($sesion->fresh()->finalizada_en);

        $this->post(route('configuracion.sesiones.reactivar', $sesion))->assertRedirect();
        $this->assertSame('preparada', $sesion->fresh()->estado);
        $this->assertNull($sesion->fresh()->finalizada_en);
    }

    public function test_configura_y_elimina_una_votacion_en_general_de_un_articulo(): void
    {
        $this->seed(RolesSeeder::class);
        $root = User::factory()->create(['rol_id' => Rol::where('slug', 'root')->value('id')]);
        $sesion = SesionParlamentaria::factory()->create();

        $this->actingAs($root)->post(route('configuracion.sesiones.votaciones.store', $sesion), [
            'tema' => 'Aprobación del proyecto de ley de presupuesto',
        ])->assertRedirect();

        $votacion = $sesion->votaciones()->firstOrFail();
        $this->assertSame('general', $votacion->tipo);
        $this->assertSame(1, $votacion->cantidad_articulos);
        $this->assertSame('directo', $votacion->metodo);
        $this->assertSame('configurada', $votacion->estado);

        $this->delete(route('configuracion.sesiones.votaciones.destroy', [$sesion, $votacion]))->assertRedirect();
        $this->assertDatabaseMissing('votaciones', ['id' => $votacion->id]);
    }
}
