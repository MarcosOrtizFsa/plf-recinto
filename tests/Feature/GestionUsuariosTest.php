<?php

namespace Tests\Feature;

use App\Models\Banca;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\BancasSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GestionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected User $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesSeeder::class, BancasSeeder::class]);
        $this->root = User::factory()->create(['rol_id' => Rol::where('slug', 'root')->value('id')]);
    }

    public function test_solo_root_puede_acceder_a_la_administracion(): void
    {
        $invitado = User::factory()->create(['rol_id' => Rol::where('slug', 'invitados')->value('id')]);

        $this->actingAs($invitado)->get('/usuarios')->assertForbidden();
        $this->actingAs($this->root)->get('/usuarios')->assertOk();
    }

    public function test_root_crea_un_legislador_con_banca(): void
    {
        $rol = Rol::where('slug', 'legisladores')->firstOrFail();
        $banca = Banca::firstOrFail();

        $this->actingAs($this->root)->post('/usuarios', [
            'nombre' => 'Legisladora Uno', 'email' => 'legisladora@example.test', 'rol_id' => $rol->id,
            'banca_id' => $banca->id, 'contrasena' => 'clave-segura', 'contrasena_confirmation' => 'clave-segura', 'activo' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('usuarios', ['email' => 'legisladora@example.test', 'banca_id' => $banca->id, 'activo' => true]);
    }

    public function test_un_legislador_requiere_banca_y_no_puede_repetirla(): void
    {
        $rol = Rol::where('slug', 'legisladores')->firstOrFail();
        $banca = Banca::firstOrFail();
        User::factory()->create(['rol_id' => $rol->id, 'banca_id' => $banca->id]);
        $datos = ['nombre' => 'Otra Persona', 'email' => 'otra@example.test', 'rol_id' => $rol->id, 'contrasena' => 'clave-segura', 'contrasena_confirmation' => 'clave-segura', 'activo' => true];

        $this->actingAs($this->root)->post('/usuarios', $datos)->assertSessionHasErrors('banca_id');
        $this->actingAs($this->root)->post('/usuarios', [...$datos, 'banca_id' => $banca->id])->assertSessionHasErrors('banca_id');
    }

    public function test_desactivar_preserva_el_usuario_y_root_no_puede_desactivarse_a_si_mismo(): void
    {
        $invitado = User::factory()->create(['rol_id' => Rol::where('slug', 'invitados')->value('id')]);

        $this->actingAs($this->root)->delete(route('usuarios.destroy', $invitado))->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('usuarios', ['id' => $invitado->id, 'activo' => false]);
        $this->actingAs($this->root)->delete(route('usuarios.destroy', $this->root))->assertForbidden();
    }
}
