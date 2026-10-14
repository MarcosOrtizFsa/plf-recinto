<?php

namespace App\Actions\Documentos;

use App\Contracts\AlmacenamientoDocumentos;
use App\Models\Documento;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Throwable;

class CrearDocumentoManual
{
    public function __construct(private readonly AlmacenamientoDocumentos $almacenamiento) {}

    /** Persiste archivo y metadatos; elimina el archivo si falla la base de datos. */
    public function ejecutar(string $titulo, UploadedFile $archivo, User $creador): Documento
    {
        $metadatos = $this->almacenamiento->guardar($archivo);

        try {
            return Documento::query()->create([...$metadatos, 'titulo' => $titulo, 'origen' => 'manual', 'activo' => true, 'creado_por' => $creador->id]);
        } catch (Throwable $excepcion) {
            $this->almacenamiento->eliminar($metadatos['ruta']);
            throw $excepcion;
        }
    }
}
