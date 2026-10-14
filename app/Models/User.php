<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = ['rol_id', 'banca_id', 'nombre', 'email', 'contrasena', 'activo'];

    protected $hidden = ['contrasena', 'token_recordarme'];

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    public function getRememberTokenName(): string
    {
        return 'token_recordarme';
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function banca(): BelongsTo
    {
        return $this->belongsTo(Banca::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'usuario_id');
    }

    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'usuario_id');
    }

    public function tieneRol(string $rol): bool
    {
        return $this->rol?->slug === $rol;
    }

    protected function casts(): array
    {
        return ['email_verificado_en' => 'datetime', 'ultimo_acceso_en' => 'datetime', 'contrasena' => 'hashed', 'activo' => 'boolean'];
    }
}
