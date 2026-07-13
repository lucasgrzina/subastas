<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Currencies\CreateCurrencyRequest;
use App\Http\Requests\Currencies\UpdateCurrencyRequest;
use App\Http\Resources\V1\CurrencyResource;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(
        private CurrencyService $currencyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            if (! $request->user()->can('currencies.read')) {
                return $this->makeError(null, 'No tenés permiso para ver monedas.', 403);
            }

            $filters = $request->only(['search', 'date_from', 'date_to']);
            $perPage = (int) $request->get('per_page', 15);

            $paginator = $this->currencyService->list($filters, $perPage);

            return $this->makeSuccessPagination($paginator, CurrencyResource::class);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function store(CreateCurrencyRequest $request): JsonResponse
    {
        try {
            if (! $request->user()->can('currencies.create')) {
                return $this->makeError(null, 'No tenés permiso para crear monedas.', 403);
            }

            $currency = $this->currencyService->create($request->validated());

            return $this->makeSuccess(new CurrencyResource($currency), 'Moneda creada correctamente.', 201);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function show(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('currencies.read')) {
                return $this->makeError(null, 'No tenés permiso para ver monedas.', 403);
            }

            $currency = $this->currencyService->findByGuid($guid);

            if (! $currency) {
                return $this->makeNotFound('Moneda no encontrada.');
            }

            return $this->makeSuccess(new CurrencyResource($currency));
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function update(UpdateCurrencyRequest $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('currencies.update')) {
                return $this->makeError(null, 'No tenés permiso para editar monedas.', 403);
            }

            $currency = $this->currencyService->findByGuid($guid);

            if (! $currency) {
                return $this->makeNotFound('Moneda no encontrada.');
            }

            $currency = $this->currencyService->update($currency, $request->validated());

            return $this->makeSuccess(new CurrencyResource($currency), 'Moneda actualizada correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function destroy(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('currencies.delete')) {
                return $this->makeError(null, 'No tenés permiso para eliminar monedas.', 403);
            }

            $currency = $this->currencyService->findByGuid($guid);

            if (! $currency) {
                return $this->makeNotFound('Moneda no encontrada.');
            }

            $this->currencyService->destroy($currency);

            return $this->makeSuccess(null, 'Moneda eliminada correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }
}
