<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Banca extends Model
{
    use HasFactory;

    protected $table = 'bancas';

    protected $fillable = ['numero', 'nombre', 'activa', 'motivo_anulacion', 'anulada_en'];

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    protected function casts(): array
    {
        return ['activa' => 'boolean', 'anulada_en' => 'datetime'];
    }
}
