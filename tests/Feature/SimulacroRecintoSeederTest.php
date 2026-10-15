<?php

namespace Tests\Feature;

use App\Models\SesionParlamentaria;
use App\Models\User;
use Database\Seeders\SimulacroRecintoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulacroRecintoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_treinta_legisladores_con_bancas_y_una_sesion_de_simulacro(): void
    {
        config()->set('recinto.simulacro.contrasena', 'clave-de-prueba');
        $this->seed(SimulacroRecintoSeeder::class);

        $legisladores = User::query()->whereHas('rol', fn ($q) => $q->where('slug', 'legisladores'))->get();
        $this->assertCount(30, $legisladores);
        $this->assertSame(30, $legisladores->pluck('banca_id')->unique()->count());
        $this->assertTrue($legisladores->every(fn (User $usuario) => str_ends_with($usuario->email, '@recinto.test')));
        $this->assertTrue(SesionParlamentaria::query()->where('numero', 'SIM-001/2026')->where('estado', 'borrador')->exists());
    }
}
