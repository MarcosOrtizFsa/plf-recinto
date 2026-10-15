<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Votacion extends Model
{
    use HasFactory;

    protected $table = 'votaciones';

    protected $guarded = [];

    public function sesionParlamentaria(): BelongsTo
    {
        return $this->belongsTo(SesionParlamentaria::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class);
    }

    protected function casts(): array
    {
        return ['cantidad_articulos' => 'integer', 'iniciada_en' => 'datetime', 'finaliza_en' => 'datetime', 'cerrada_en' => 'datetime', 'resultado_publicado' => 'boolean'];
    }
}
