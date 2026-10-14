<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SesionParlamentaria extends Model
{
    use HasFactory;

    protected $table = 'sesiones_parlamentarias';

    protected $guarded = [];

    public function documentos(): BelongsToMany
    {
        return $this->belongsToMany(Documento::class, 'documentos_sesion')->withPivot(['orden', 'activo'])->withTimestamps();
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function votaciones(): HasMany
    {
        return $this->hasMany(Votacion::class);
    }

    public function estadoRecinto(): HasOne
    {
        return $this->hasOne(EstadoRecinto::class);
    }

    protected function casts(): array
    {
        return ['fecha_inicio_programada' => 'datetime', 'iniciada_en' => 'datetime', 'finalizada_en' => 'datetime'];
    }
}
