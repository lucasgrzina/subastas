<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auctions\CreateAuctionRequest;
use App\Http\Requests\Auctions\UpdateAuctionRequest;
use App\Http\Resources\V1\AuctionResource;
use App\Services\AuctionService;
use App\Support\BidderVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuctionController extends Controller
{
    public function __construct(
        private AuctionService $auctionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            if (! $request->user()->can('auctions.read')) {
                return $this->makeError(null, 'No tenés permiso para ver subastas.', 403);
            }

            $filters = $request->only(['search', 'date_from', 'date_to']);
            $perPage = (int) $request->get('per_page', 15);
            $isBidder = $request->user()->hasRole('bidder');

            $paginator = $this->auctionService->list($filters, $perPage, $isBidder);

            return $this->makeSuccessPagination($paginator, AuctionResource::class);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function store(CreateAuctionRequest $request): JsonResponse
    {
        try {
            if (! $request->user()->can('auctions.create')) {
                return $this->makeError(null, 'No tenés permiso para crear subastas.', 403);
            }

            $auction = $this->auctionService->create($request->validated());

            return $this->makeSuccess(new AuctionResource($auction), 'Subasta creada correctamente.', 201);
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function show(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('auctions.read')) {
                return $this->makeError(null, 'No tenés permiso para ver subastas.', 403);
            }

            $auction = $this->auctionService->findByGuid($guid);

            if (! $auction) {
                return $this->makeNotFound('Subasta no encontrada.');
            }

            if ($request->user()->hasRole('bidder') && ! BidderVisibility::auctionVisible($auction)) {
                return $this->makeNotFound('Subasta no encontrada.');
            }

            return $this->makeSuccess(new AuctionResource($auction));
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function update(UpdateAuctionRequest $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('auctions.update')) {
                return $this->makeError(null, 'No tenés permiso para editar subastas.', 403);
            }

            $auction = $this->auctionService->findByGuid($guid);

            if (! $auction) {
                return $this->makeNotFound('Subasta no encontrada.');
            }

            $auction = $this->auctionService->update($auction, $request->validated());

            return $this->makeSuccess(new AuctionResource($auction), 'Subasta actualizada correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }

    public function destroy(Request $request, string $guid): JsonResponse
    {
        try {
            if (! $request->user()->can('auctions.delete')) {
                return $this->makeError(null, 'No tenés permiso para eliminar subastas.', 403);
            }

            $auction = $this->auctionService->findByGuid($guid);

            if (! $auction) {
                return $this->makeNotFound('Subasta no encontrada.');
            }

            $this->auctionService->destroy($auction);

            return $this->makeSuccess(null, 'Subasta eliminada correctamente.');
        } catch (\Exception $e) {
            return $this->makeFromException($e);
        }
    }
}
