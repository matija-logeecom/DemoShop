<?php

namespace DemoShop\Business\Interfaces\Repository;

use DemoShop\Data\Model\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

interface ProductRepositoryInterface
{
    /**
     * Creates a new product in the database.
     *
     * @param array $productData
     *
     * @return bool
     *
     * @throws RuntimeException
     */
    public function create(array $productData): bool;

    /**
     * Retrieves all products on the provided page
     *
     * @param int $page
     * @param int $perPage
     * @param array $filters
     *
     * @return LengthAwarePaginator
     *
     * @throws RuntimeException
     */
    public function getAll(int $page = 1, int $perPage = 10, array $filters = []): LengthAwarePaginator;

    /**
     * Returns a collection of products with provided IDs
     *
     * @param array $productIds
     *
     * @return Collection
     *
     * @throws RuntimeException
     */
    public function findByIds(array $productIds): Collection;

    /**
     * Returns a product with the provided SKU
     *
     * @param string $sku
     *
     * @return Product|null
     *
     * @throws RuntimeException
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Deletes all products with provided IDs
     *
     * @param array $productIds
     *
     * @return int
     *
     * @throws RuntimeException
     */
    public function deleteByIds(array $productIds): int;

    /**
     * Updates the 'is_enabled' status for a list of product IDs.
     *
     * @param array $productIds
     * @param bool $isEnabled
     *
     * @return int
     *
     * @throws RuntimeException
     */
    public function updateIsEnabledStatus(array $productIds, bool $isEnabled): int;

    /**
     * Checks if any products exist within the given category IDs.
     *
     * @param array $categoryIds
     *
     * @return bool
     */
    public function hasProductsInCategories(array $categoryIds): bool;
}