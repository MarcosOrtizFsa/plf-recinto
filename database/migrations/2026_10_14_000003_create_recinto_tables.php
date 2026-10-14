<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_parlamentarias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('numero', 50)->nullable()->index();
            $table->dateTime('fecha_inicio_programada')->nullable();
            $table->dateTime('iniciada_en')->nullable();
            $table->dateTime('finalizada_en')->nullable();
            $table->enum('estado', ['borrador', 'preparada', 'en_curso', 'finalizada', 'cancelada'])->default('borrador')->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('nombre_archivo');
            $table->string('ruta')->comment('Ruta en almacenamiento privado; nunca una URL pública');
            $table->string('disco', 40)->default('local');
            $table->enum('origen', ['manual', 'api'])->default('manual')->index();
            $table->string('referencia_externa')->nullable()->index();
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('hash_sha256', 64)->nullable()->index();
            $table->boolean('activo')->default(true)->index();
            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('documentos_sesion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_parlamentaria_id')->constrained('sesiones_parlamentarias')->cascadeOnDelete();
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true)->comment('Disponible para usarse en esta sesión');
            $table->timestamps();
            $table->unique(['sesion_parlamentaria_id', 'documento_id']);
        });

        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_parlamentaria_id')->constrained('sesiones_parlamentarias')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->enum('estado', ['presente', 'ausente', 'justificado'])->default('ausente');
            $table->timestamp('registrada_en')->nullable();
            $table->foreignId('registrada_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->unique(['sesion_parlamentaria_id', 'usuario_id']);
        });

        Schema::create('votaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_parlamentaria_id')->constrained('sesiones_parlamentarias')->cascadeOnDelete();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->string('tema');
            $table->text('descripcion')->nullable();
            $table->enum('metodo', ['asistido', 'directo']);
            $table->enum('estado', ['configurada', 'abierta', 'cerrada', 'anulada'])->default('configurada')->index();
            $table->unsignedInteger('duracion_segundos')->nullable();
            $table->timestamp('iniciada_en')->nullable();
            $table->timestamp('finaliza_en')->nullable();
            $table->timestamp('cerrada_en')->nullable();
            $table->boolean('resultado_publicado')->default(false);
            $table->timestamps();
        });

        Schema::create('votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('votacion_id')->constrained('votaciones')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('banca_id')->nullable()->constrained('bancas')->nullOnDelete();
            $table->enum('opcion', ['afirmativo', 'negativo', 'abstencion']);
            $table->foreignId('registrado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('emitido_en');
            $table->timestamps();
            $table->unique(['votacion_id', 'usuario_id']);
        });

        Schema::create('estado_recinto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_parlamentaria_id')->nullable()->unique()->constrained('sesiones_parlamentarias')->nullOnDelete();
            $table->foreignId('documento_mostrado_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->foreignId('votacion_activa_id')->nullable()->constrained('votaciones')->nullOnDelete();
            $table->enum('vista_monitor', ['espera', 'asistencia', 'documento', 'votacion', 'resultado'])->default('espera');
            $table->unsignedBigInteger('version')->default(1)->comment('Permite detectar estados obsoletos al resincronizar');
            $table->json('configuracion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estado_recinto');
        Schema::dropIfExists('votos');
        Schema::dropIfExists('votaciones');
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('documentos_sesion');
        Schema::dropIfExists('documentos');
        Schema::dropIfExists('sesiones_parlamentarias');
    }
};
