<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Subida diferida (staged upload) de imágenes.
 *
 * Flujo:
 *   1. store()   → recibe el binario, lo re-codifica a webp (destruye payloads),
 *                  lo guarda en la carpeta temporal y devuelve un token.
 *   2. promote() → al guardar el modelo, mueve el temporal a su carpeta final.
 *
 * El servidor solo promueve archivos que él mismo puso en la carpeta temporal;
 * nunca confía en un path arbitrario del cliente.
 */
class TempUploadService
{
    /** Formato de token válido: <uuid>.webp */
    private const TOKEN_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.webp$/';

    /**
     * Re-codifica y guarda la imagen en la carpeta temporal.
     *
     * @return array{token: string, url: string}
     */
    public function store(UploadedFile $file): array
    {
        $disk = config('uploads.disk');
        $folder = config('uploads.temp_folder', 'tmp/uploads');
        $maxDim = (int) config('uploads.max_dimension', 2048);

        // Re-codificación: decodifica y vuelve a encodear con GD → neutraliza payloads.
        $manager = new ImageManager(new Driver);
        $image = $manager->decode($file->getRealPath());
        $image->scaleDown(width: $maxDim, height: $maxDim);
        $binary = (string) $image->encode(new WebpEncoder(quality: 85, strip: true));

        $token = Str::uuid()->toString().'.webp';
        $path = $folder.'/'.$token;

        Storage::disk($disk)->put($path, $binary);

        return [
            'token' => $token,
            'url' => Storage::disk($disk)->url($path),
        ];
    }

    public function isValidToken(?string $token): bool
    {
        return is_string($token) && preg_match(self::TOKEN_REGEX, $token) === 1;
    }

    public function tempExists(string $token): bool
    {
        if (! $this->isValidToken($token)) {
            return false;
        }

        return Storage::disk(config('uploads.disk'))->exists($this->tempPath($token));
    }

    /**
     * Mueve el temporal a su carpeta final y devuelve el path definitivo.
     *
     * @throws \RuntimeException si el token es inválido o el temporal no existe
     */
    public function promote(string $token, string $targetFolder): string
    {
        if (! $this->isValidToken($token)) {
            throw new \RuntimeException("Token de imagen inválido: {$token}");
        }

        $disk = config('uploads.disk');
        $tempPath = $this->tempPath($token);

        if (! Storage::disk($disk)->exists($tempPath)) {
            throw new \RuntimeException("Imagen temporal inexistente: {$token}");
        }

        $finalPath = trim($targetFolder, '/').'/'.$token;
        Storage::disk($disk)->move($tempPath, $finalPath);

        return $finalPath;
    }

    /** Borra un archivo del disco (falla-suave). */
    public function deletePath(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk(config('uploads.disk'))->delete($path);
        } catch (\Throwable $e) {
            Log::warning('No se pudo borrar el archivo', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }

    /** Borra temporales más viejos que temp_ttl_hours. Devuelve la cantidad borrada. */
    public function cleanupExpired(): int
    {
        $disk = config('uploads.disk');
        $folder = config('uploads.temp_folder', 'tmp/uploads');
        $ttl = (int) config('uploads.temp_ttl_hours', 24);
        $cutoff = now()->subHours($ttl)->getTimestamp();
        $deleted = 0;

        foreach (Storage::disk($disk)->files($folder) as $file) {
            if (Storage::disk($disk)->lastModified($file) < $cutoff) {
                Storage::disk($disk)->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    private function tempPath(string $token): string
    {
        return config('uploads.temp_folder', 'tmp/uploads').'/'.$token;
    }
}
