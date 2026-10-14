<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\SesionParlamentaria;
use App\Models\User;
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
}
