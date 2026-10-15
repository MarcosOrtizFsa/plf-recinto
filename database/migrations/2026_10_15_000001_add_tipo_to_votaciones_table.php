<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votaciones', function (Blueprint $table) {
            $table->string('tipo', 30)->default('general')->after('tema');
            $table->unsignedInteger('cantidad_articulos')->default(1)->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('votaciones', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'cantidad_articulos']);
        });
    }
};
