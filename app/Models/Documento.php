<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos';

    protected $guarded = [];

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function sesiones(): BelongsToMany
    {
        return $this->belongsToMany(SesionParlamentaria::class, 'documentos_sesion')->withPivot(['orden', 'activo'])->withTimestamps();
    }

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
