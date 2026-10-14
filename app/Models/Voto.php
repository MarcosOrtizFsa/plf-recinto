<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voto extends Model
{
    use HasFactory;

    protected $table = 'votos';

    protected $guarded = [];

    public function votacion(): BelongsTo
    {
        return $this->belongsTo(Votacion::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function banca(): BelongsTo
    {
        return $this->belongsTo(Banca::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    protected function casts(): array
    {
        return ['emitido_en' => 'datetime'];
    }
}
