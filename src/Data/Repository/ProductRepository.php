<?php

namespace DemoShop\Data\Repository;

use DemoShop\Business\Interfaces\Repository\ProductRepositoryInterface;
use DemoShop\Data\Model\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use Exception;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function create(array $productData): bool
    {
        try {
            $product = Product::create($productData);

            return $product instanceof Product && $product->exists;
        } catch (QueryException $e) {
            error_log("create - Database query failed: " . $e->getMessage());
            throw new RuntimeException("Failed to create product due to a database error.", 0, $e);
        } catch (Exception $e) {
            error_log("create - An unexpected error occurred: " . $e->getMessage());
            throw new RuntimeException("An unexpected error occurred while creating the product.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getAll(int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        try {
            return Product::orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
        } catch (QueryException $e) {
            error_log("getAll - Database query failed: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve products due to a database error.", 0, $e);
        } catch (Exception $e) {
            error_log("getAll - An unexpected error occurred: " . $e->getMessage());
            throw new RuntimeException("An unexpected error occurred while retrieving products.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findByIds(array $productIds): Collection
    {
        if (empty($productIds)) {
            return new Collection();
        }

        try {
            return Product::whereIn('id', $productIds)->get();
        } catch (QueryException $e) {
            error_log("findByIds - Database query failed: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve products due to a database error.", 0, $e);
        } catch (Exception $e) {
            error_log("findByIds - An unexpected error occurred: " . $e->getMessage());
            throw new RuntimeException("An unexpected error occurred while retrieving products.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findBySku(string $sku): ?Product
    {
        try {
            return Product::where('sku', $sku)->first();
        } catch (QueryException $e) {
            error_log("findBySku - Database query failed: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve product due to a database error.", 0, $e);
        } catch (Exception $e) {
            error_log("findBySku - An unexpected error occurred: " . $e->getMessage());
            throw new RuntimeException("An unexpected error occurred while retrieving products.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteByIds(array $productIds): int
    {
        try {
            return Product::destroy($productIds);
        } catch (QueryException $e) {
            error_log("deleteByIds - Database query failed: " . $e->getMessage());
            throw new RuntimeException("Failed to delete products due to a database error.", 0, $e);
        } catch (Exception $e) {
            error_log("deleteByIds - An unexpected error occurred: " . $e->getMessage());
            throw new RuntimeException("An unexpected error occurred while deleting products.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function updateIsEnabledStatus(array $productIds, bool $isEnabled): int // <-- IMPLEMENT THIS METHOD
    {
        try {
            return Product::whereIn('id', $productIds)->update(['is_enabled' => $isEnabled]);
        } catch (QueryException $e) {
            error_log("updateIsEnabledStatus - Database query failed: " . $e->getMessage());
            throw new RuntimeException("Failed to update product status due to a database error.", 0, $e);
        } catch (Exception $e) {
            error_log("updateIsEnabledStatus - An unexpected error occurred: " . $e->getMessage());
            throw new RuntimeException(
                "An unexpected error occurred while updating product status.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function hasProductsInCategories(array $categoryIds): bool
    {
        if (empty($categoryIds)) {
            return false;
        }

        return Product::whereIn('category_id', $categoryIds)->exists();
    }
}
