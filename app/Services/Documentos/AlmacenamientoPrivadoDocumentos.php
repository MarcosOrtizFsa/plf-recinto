<?php

namespace App\Services\Documentos;

use App\Contracts\AlmacenamientoDocumentos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlmacenamientoPrivadoDocumentos implements AlmacenamientoDocumentos
{
    private const DISCO = 'local';

    public function guardar(UploadedFile $archivo): array
    {
        $nombre = Str::uuid().'.pdf';
        $ruta = $archivo->storeAs('documentos', $nombre, self::DISCO);

        if (! $ruta) {
            throw new RuntimeException('No fue posible guardar el documento.');
        }

        return [
            'ruta' => $ruta,
            'disco' => self::DISCO,
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'mime_type' => $archivo->getMimeType() ?: 'application/pdf',
            'tamano_bytes' => $archivo->getSize(),
            'hash_sha256' => hash_file('sha256', $archivo->getRealPath()),
        ];
    }

    public function eliminar(string $ruta): void
    {
        Storage::disk(self::DISCO)->delete($ruta);
    }

    public function visualizar(string $ruta, string $nombreArchivo): StreamedResponse
    {
        abort_unless(Storage::disk(self::DISCO)->exists($ruta), 404);

        return Storage::disk(self::DISCO)->response($ruta, $nombreArchivo, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $nombreArchivo).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
