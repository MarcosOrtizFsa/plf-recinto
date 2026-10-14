<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Rol;
use App\Models\SesionParlamentaria;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfiguracionDocumentosTest extends TestCase
{
    use RefreshDatabase;

    protected User $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->root = User::factory()->create(['rol_id' => Rol::where('slug', 'root')->value('id')]);
        Storage::fake('local');
    }

    public function test_root_carga_un_pdf_en_almacenamiento_privado(): void
    {
        $archivo = UploadedFile::fake()->create('orden-del-dia.pdf', 100, 'application/pdf');

        $this->actingAs($this->root)->post(route('configuracion.documentos.store'), ['titulo' => 'Orden del día', 'archivo' => $archivo])->assertRedirect(route('configuracion.documentos.index'));

        $documento = Documento::firstOrFail();
        $this->assertSame('manual', $documento->origen);
        $this->assertSame('orden-del-dia.pdf', $documento->nombre_archivo);
        Storage::disk('local')->assertExists($documento->ruta);
    }

    public function test_rechaza_un_archivo_que_no_es_pdf(): void
    {
        $archivo = UploadedFile::fake()->create('notas.txt', 10, 'text/plain');
        $this->actingAs($this->root)->post(route('configuracion.documentos.store'), ['titulo' => 'Notas', 'archivo' => $archivo])->assertSessionHasErrors('archivo');
        $this->assertDatabaseCount('documentos', 0);
    }

    public function test_secretaria_puede_configurar_pero_un_invitado_no(): void
    {
        $secretaria = User::factory()->create(['rol_id' => Rol::where('slug', 'secretaria')->value('id')]);
        $invitado = User::factory()->create(['rol_id' => Rol::where('slug', 'invitados')->value('id')]);

        $this->actingAs($secretaria)->get(route('configuracion.documentos.index'))->assertOk();
        $this->actingAs($invitado)->get(route('configuracion.documentos.index'))->assertForbidden();
    }

    public function test_activa_y_desactiva_un_documento_por_sesion(): void
    {
        $sesion = SesionParlamentaria::factory()->create();
        $documento = Documento::factory()->create();

        $this->actingAs($this->root)->put(route('configuracion.sesiones.documentos.update', [$sesion, $documento]), ['activo' => true])->assertRedirect();
        $this->assertDatabaseHas('documentos_sesion', ['sesion_parlamentaria_id' => $sesion->id, 'documento_id' => $documento->id, 'activo' => true]);

        $this->actingAs($this->root)->put(route('configuracion.sesiones.documentos.update', [$sesion, $documento]), ['activo' => false])->assertRedirect();
        $this->assertDatabaseHas('documentos_sesion', ['sesion_parlamentaria_id' => $sesion->id, 'documento_id' => $documento->id, 'activo' => false]);
    }

    public function test_archivar_un_documento_lo_desactiva_en_todas_las_sesiones(): void
    {
        $documento = Documento::factory()->create(['activo' => true]);
        $sesion = SesionParlamentaria::factory()->create();
        $sesion->documentos()->attach($documento, ['activo' => true, 'orden' => 1]);

        $this->actingAs($this->root)->patch(route('configuracion.documentos.estado', $documento))->assertRedirect();
        $this->assertFalse($documento->fresh()->activo);
        $this->assertDatabaseHas('documentos_sesion', ['documento_id' => $documento->id, 'activo' => false]);
    }
}
