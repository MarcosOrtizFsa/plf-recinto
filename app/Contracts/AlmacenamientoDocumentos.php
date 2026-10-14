<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface AlmacenamientoDocumentos
{
    /** Guarda un documento y devuelve metadatos independientes del proveedor físico. */
    public function guardar(UploadedFile $archivo): array;

    public function eliminar(string $ruta): void;

    public function visualizar(string $ruta, string $nombreArchivo): StreamedResponse;
}
