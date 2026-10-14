<?php

namespace Tests\Feature;

use App\Models\Banca;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\BancasSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RootSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FundacionRecintoTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_las_tablas_principales(): void
    {
        foreach (['roles', 'usuarios', 'bancas', 'sesiones_parlamentarias', 'documentos', 'documentos_sesion', 'asistencias', 'votaciones', 'votos', 'estado_recinto'] as $tabla) {
            $this->assertTrue(Schema::hasTable($tabla), "Falta la tabla {$tabla}");
        }
    }

    public function test_los_seeders_crean_seis_roles_y_treinta_bancas(): void
    {
        $this->seed([RolesSeeder::class, BancasSeeder::class]);

        $this->assertSame(6, Rol::query()->count());
        $this->assertSame(30, Banca::query()->count());
        $this->assertSame(range(1, 30), Banca::query()->orderBy('numero')->pluck('numero')->all());
    }

    public function test_el_usuario_root_usa_exclusivamente_la_configuracion_del_entorno(): void
    {
        config()->set('recinto.root', ['nombre' => 'Root de prueba', 'email' => 'root@prueba.test', 'contrasena' => 'secreto-prueba']);
        $this->seed([RolesSeeder::class, RootSeeder::class]);

        $root = User::query()->where('email', 'root@prueba.test')->firstOrFail();
        $this->assertSame('Root de prueba', $root->nombre);
        $this->assertTrue($root->tieneRol('root'));
        $this->assertNotSame('secreto-prueba', $root->getAuthPassword());
    }
}
