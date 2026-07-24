<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\CreateProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            if (! $request->user()->can('products.read')) {
                return $this->makeError(null, 'No tenés permiso para ver productos.', 403);
            }

            $filters = $request->only(['search', 'status', 'date_from', 'date_to', 'with_trashed']);
            $perPage = (int) $request->get('per_page', 15);

            $paginator = $this->productService->list($filters, $perPage);

            return $this->makeSuccessPagination($paginator, ProductResource::class);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        try {
            if (! $request->user()->can('products.create')) {
                return $this->makeError(null, 'No tenés permiso para crear productos.', 403);
            }

            $product = $this->productService->create($request->validated());

            return $this->makeSuccess(new ProductResource($product), 'Producto creado correctamente.', 201);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function show(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('products.read')) {
                return $this->makeError(null, 'No tenés permiso para ver productos.', 403);
            }

            $product = $this->productService->findByGuid($guid);

            if (! $product) {
                return $this->makeNotFound('Producto no encontrado.');
            }

            return $this->makeSuccess(new ProductResource($product));
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function update(UpdateProductRequest $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('products.update')) {
                return $this->makeError(null, 'No tenés permiso para editar productos.', 403);
            }

            $product = $this->productService->findByGuid($guid);

            if (! $product) {
                return $this->makeNotFound('Producto no encontrado.');
            }

            $product = $this->productService->update($product, $request->validated());

            return $this->makeSuccess(new ProductResource($product), 'Producto actualizado correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function destroy(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('products.delete')) {
                return $this->makeError(null, 'No tenés permiso para eliminar productos.', 403);
            }

            $product = $this->productService->findByGuid($guid);

            if (! $product) {
                return $this->makeNotFound('Producto no encontrado.');
            }

            $this->productService->destroy($product);

            return $this->makeSuccess(null, 'Producto eliminado correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function restore(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('products.update')) {
                return $this->makeError(null, 'No tenés permiso para restaurar productos.', 403);
            }

            $product = $this->productService->findByGuidWithTrashed($guid);

            if (! $product) {
                return $this->makeNotFound('Producto no encontrado.');
            }

            $product = $this->productService->restore($product);

            return $this->makeSuccess(new ProductResource($product), 'Producto restaurado correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }
}
