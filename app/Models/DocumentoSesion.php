<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DocumentoSesion extends Pivot
{
    use HasFactory;

    protected $table = 'documentos_sesion';

    protected $guarded = [];

    public $incrementing = true;

    public function sesionParlamentaria(): BelongsTo
    {
        return $this->belongsTo(SesionParlamentaria::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
