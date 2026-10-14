<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use HasFactory;

    protected $table = 'asistencias';

    protected $guarded = [];

    public function sesionParlamentaria(): BelongsTo
    {
        return $this->belongsTo(SesionParlamentaria::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }

    protected function casts(): array
    {
        return ['registrada_en' => 'datetime'];
    }
}
