<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstadoRecinto extends Model
{
    use HasFactory;

    protected $table = 'estado_recinto';

    protected $guarded = [];

    public function sesionParlamentaria(): BelongsTo
    {
        return $this->belongsTo(SesionParlamentaria::class);
    }

    public function documentoMostrado(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'documento_mostrado_id');
    }

    public function votacionActiva(): BelongsTo
    {
        return $this->belongsTo(Votacion::class, 'votacion_activa_id');
    }

    protected function casts(): array
    {
        return ['configuracion' => 'array'];
    }
}
