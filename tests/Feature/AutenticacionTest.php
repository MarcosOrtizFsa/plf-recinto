<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    public function test_un_usuario_activo_puede_iniciar_y_cerrar_sesion(): void
    {
        $usuario = User::factory()->create(['rol_id' => Rol::where('slug', 'root')->value('id'), 'contrasena' => 'clave-segura', 'activo' => true]);

        $this->post('/login', ['email' => $usuario->email, 'password' => 'clave-segura', 'remember' => true])->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($usuario);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_un_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $usuario = User::factory()->create(['rol_id' => Rol::where('slug', 'invitados')->value('id'), 'contrasena' => 'clave-segura', 'activo' => false]);

        $this->post('/login', ['email' => $usuario->email, 'password' => 'clave-segura'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_no_existe_registro_publico(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }
}
