<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Uploads\StoreTempImageRequest;
use App\Services\TempUploadService;
use Illuminate\Http\JsonResponse;

class TempUploadController extends Controller
{
    public function __construct(
        private TempUploadService $tempUploadService,
    ) {}

    /**
     * Sube una imagen al área temporal y devuelve un token + URL de preview.
     * Genérico y reusable por cualquier módulo.
     */
    public function store(StoreTempImageRequest $request): JsonResponse
    {
        try {
            $result = $this->tempUploadService->store($request->file('image'));

            return $this->makeSuccess($result, 'Imagen subida correctamente.', 201);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }
}
