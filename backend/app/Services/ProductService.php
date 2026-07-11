<?php

namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Criteria\Products\ProductStatusCriteria;
use App\Criteria\Shared\DateRangeCriteria;
use App\Criteria\Shared\SearchCriteria;
use App\Models\GrapeVariety;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductWineDetail;
use App\Models\WineRegion;
use App\Models\Winery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private TempUploadService $tempUploadService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $withTrashed = (bool) ($filters['with_trashed'] ?? false);

        return $this->productRepository->list(
            $perPage,
            $withTrashed,
            new SearchCriteria($filters['search'] ?? null, 'title'),
            new DateRangeCriteria($filters['date_from'] ?? null, $filters['date_to'] ?? null),
            new ProductStatusCriteria($filters['status'] ?? null),
        );
    }

    public function findByGuid(string $guid): ?Product
    {
        return $this->productRepository->findByGuid($guid);
    }

    public function findByGuidWithTrashed(string $guid): ?Product
    {
        return $this->productRepository->findByGuidWithTrashed($guid);
    }

    public function create(array $data): Product
    {
        $winery = Winery::where('guid', $data['wine_details']['winery_guid'])->firstOrFail();
        $grapeVariety = GrapeVariety::where('guid', $data['wine_details']['grape_variety_guid'])->firstOrFail();
        $wineRegion = WineRegion::where('guid', $data['wine_details']['wine_region_guid'])->firstOrFail();

        $promotedPaths = [];

        try {
            return DB::transaction(function () use ($data, $winery, $grapeVariety, $wineRegion, &$promotedPaths) {
                $product = $this->productRepository->create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'status' => $data['status'] ?? 'draft',
                ]);

                ProductWineDetail::create([
                    'product_id' => $product->id,
                    'year' => $data['wine_details']['year'],
                    'winery_id' => $winery->id,
                    'grape_variety_id' => $grapeVariety->id,
                    'wine_region_id' => $wineRegion->id,
                ]);

                foreach ($data['images'] as $image) {
                    $finalPath = $this->tempUploadService->promote($image['token'], "products/{$product->guid}");
                    $promotedPaths[] = $finalPath;

                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $finalPath,
                        'is_main' => (bool) $image['is_main'],
                    ]);
                }

                return $product->fresh(['wineDetail.winery', 'wineDetail.grapeVariety', 'wineDetail.wineRegion', 'images']);
            });
        } catch (\Throwable $e) {
            foreach ($promotedPaths as $path) {
                $this->tempUploadService->deletePath($path);
            }

            throw $e;
        }
    }

    public function update(Product $product, array $data): Product
    {
        $promotedPaths = [];
        $pathsToDeleteAfterCommit = [];

        try {
            $updated = DB::transaction(function () use ($product, $data, &$promotedPaths, &$pathsToDeleteAfterCommit) {
                $productData = array_intersect_key($data, array_flip(['title', 'description', 'status']));
                if (! empty($productData)) {
                    $this->productRepository->update($product, $productData);
                }

                if (isset($data['wine_details'])) {
                    $winery = Winery::where('guid', $data['wine_details']['winery_guid'])->firstOrFail();
                    $grapeVariety = GrapeVariety::where('guid', $data['wine_details']['grape_variety_guid'])->firstOrFail();
                    $wineRegion = WineRegion::where('guid', $data['wine_details']['wine_region_guid'])->firstOrFail();

                    $product->wineDetail()->updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'year' => $data['wine_details']['year'],
                            'winery_id' => $winery->id,
                            'grape_variety_id' => $grapeVariety->id,
                            'wine_region_id' => $wineRegion->id,
                        ],
                    );
                }

                if (isset($data['images'])) {
                    $keptImageIds = [];

                    // Clear is_main on every existing row FIRST. Otherwise, whichever
                    // row we insert/update as the new is_main=true would transiently
                    // coexist with the old is_main=true row and trip the DB's partial
                    // unique index before we get a chance to remove the old one.
                    ProductImage::where('product_id', $product->id)->update(['is_main' => false]);

                    foreach ($data['images'] as $image) {
                        if (! empty($image['image_id'])) {
                            $existing = ProductImage::where('id', $image['image_id'])
                                ->where('product_id', $product->id)
                                ->firstOrFail();
                            $existing->update(['is_main' => (bool) $image['is_main']]);
                            $keptImageIds[] = $existing->id;

                            continue;
                        }

                        $finalPath = $this->tempUploadService->promote($image['token'], "products/{$product->guid}");
                        $promotedPaths[] = $finalPath;

                        $newImage = ProductImage::create([
                            'product_id' => $product->id,
                            'path' => $finalPath,
                            'is_main' => (bool) $image['is_main'],
                        ]);
                        $keptImageIds[] = $newImage->id;
                    }

                    $removedImages = ProductImage::where('product_id', $product->id)
                        ->whereNotIn('id', $keptImageIds)
                        ->get();

                    foreach ($removedImages as $removedImage) {
                        $pathsToDeleteAfterCommit[] = $removedImage->path;
                        $removedImage->delete();
                    }
                }

                return $product->fresh(['wineDetail.winery', 'wineDetail.grapeVariety', 'wineDetail.wineRegion', 'images']);
            });

            // Only delete replaced files AFTER the transaction commits successfully —
            // never before, or a rollback would lose live files with no way back.
            foreach ($pathsToDeleteAfterCommit as $path) {
                $this->tempUploadService->deletePath($path);
            }

            return $updated;
        } catch (\Throwable $e) {
            foreach ($promotedPaths as $path) {
                $this->tempUploadService->deletePath($path);
            }

            throw $e;
        }
    }

    public function destroy(Product $product): void
    {
        $this->productRepository->destroy($product);
    }

    public function restore(Product $product): Product
    {
        return $this->productRepository->restore($product);
    }
}
