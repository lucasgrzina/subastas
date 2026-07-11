<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bids\CreateBidRequest;
use App\Http\Requests\Lots\CreateLotRequest;
use App\Http\Requests\Lots\UpdateLotRequest;
use App\Http\Resources\V1\BidResource;
use App\Http\Resources\V1\LotResource;
use App\Services\LotService;
use App\Support\BidderVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function __construct(
        private LotService $lotService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            if (! $request->user()->can('lots.read')) {
                return $this->makeError(null, 'No tenés permiso para ver lotes.', 403);
            }

            $filters = $request->only(['search']);
            $perPage = (int) $request->get('per_page', 15);
            $isBidder = $request->user()->hasRole('bidder');

            $paginator = $this->lotService->list($filters, $perPage, $isBidder);

            return $this->makeSuccessPagination($paginator, LotResource::class);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function store(CreateLotRequest $request): JsonResponse
    {
        try {
            if (! $request->user()->can('lots.create')) {
                return $this->makeError(null, 'No tenés permiso para crear lotes.', 403);
            }

            $lot = $this->lotService->create($request->validated());

            return $this->makeSuccess(new LotResource($lot), 'Lote creado correctamente.', 201);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function show(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('lots.read')) {
                return $this->makeError(null, 'No tenés permiso para ver lotes.', 403);
            }

            $lot = $this->lotService->findByGuid($guid);

            if (! $lot || ($request->user()->hasRole('bidder') && ! BidderVisibility::lotVisible($lot))) {
                return $this->makeNotFound('Lote no encontrado.');
            }

            return $this->makeSuccess(new LotResource($lot));
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function update(UpdateLotRequest $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('lots.update')) {
                return $this->makeError(null, 'No tenés permiso para editar lotes.', 403);
            }

            $lot = $this->lotService->findByGuid($guid);

            if (! $lot) {
                return $this->makeNotFound('Lote no encontrado.');
            }

            $lot = $this->lotService->update($lot, $request->validated());

            return $this->makeSuccess(new LotResource($lot), 'Lote actualizado correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function destroy(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('lots.delete')) {
                return $this->makeError(null, 'No tenés permiso para eliminar lotes.', 403);
            }

            $lot = $this->lotService->findByGuid($guid);

            if (! $lot) {
                return $this->makeNotFound('Lote no encontrado.');
            }

            $this->lotService->destroy($lot);

            return $this->makeSuccess(null, 'Lote eliminado correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    /**
     * Bid history sub-resource. The bidder-visibility gate is applied HERE too,
     * not only on show() (tasks obs#41 risk #5, SUGGESTION).
     */
    public function bids(Request $request, string $guid): JsonResponse
    {
        try {
            // Gated on `lots.read` (not `bids.read`): bid history is a lot sub-resource,
            // and the `bidder` role holds exactly 3 permissions — bids.create, auctions.read,
            // lots.read (tasks obs#41 2.6/2.7) — with no standalone `bids.read` grant.
            // `bids.read` is still seeded and assigned to super-admin/admin/operador for
            // RBAC completeness (spec permission matrix), but is not the gate used here.
            if (! $request->user()->can('lots.read')) {
                return $this->makeError(null, 'No tenés permiso para ver el historial de ofertas.', 403);
            }

            $lot = $this->lotService->findByGuid($guid);

            if (! $lot || ($request->user()->hasRole('bidder') && ! BidderVisibility::lotVisible($lot))) {
                return $this->makeNotFound('Lote no encontrado.');
            }

            $perPage = (int) $request->get('per_page', 15);
            $paginator = $this->lotService->bidHistory($lot, $perPage);

            return $this->makeSuccessPagination($paginator, BidResource::class);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function storeBid(CreateBidRequest $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('bids.create')) {
                return $this->makeError(null, 'No tenés permiso para ofertar.', 403);
            }

            $lot = $this->lotService->findByGuid($guid);

            if (! $lot) {
                return $this->makeNotFound('Lote no encontrado.');
            }

            $bid = $this->lotService->placeBid($lot, $request->user(), $request->validated()['amount']);

            return $this->makeSuccess(new BidResource($bid->load('user')), 'Oferta registrada correctamente.', 201);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function close(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('lots.close')) {
                return $this->makeError(null, 'No tenés permiso para cerrar lotes.', 403);
            }

            $lot = $this->lotService->findByGuid($guid);

            if (! $lot) {
                return $this->makeNotFound('Lote no encontrado.');
            }

            $lot = $this->lotService->close($lot);

            return $this->makeSuccess(new LotResource($lot), 'Lote cerrado correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }
}
