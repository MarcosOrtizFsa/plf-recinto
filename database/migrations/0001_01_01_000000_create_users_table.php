<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('slug', 60)->unique()->comment('Clave estable usada para autorización');
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('bancas', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero')->unique()->comment('Número físico de banca, del 1 al 30');
            $table->string('nombre', 80);
            $table->boolean('activa')->default(true);
            $table->string('motivo_anulacion')->nullable();
            $table->timestamp('anulada_en')->nullable();
            $table->timestamps();
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete();
            $table->foreignId('banca_id')->nullable()->unique()->constrained('bancas')->nullOnDelete();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->timestamp('email_verificado_en')->nullable();
            $table->string('contrasena');
            $table->boolean('activo')->default(true);
            $table->string('token_recordarme', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('bancas');
        Schema::dropIfExists('roles');
    }
};
