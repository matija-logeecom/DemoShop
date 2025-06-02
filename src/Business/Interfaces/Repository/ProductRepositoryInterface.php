<?php

namespace DemoShop\Business\Interfaces\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;
use Illuminate\Database\Eloquent\Collection;

// Eloquent Model

interface ProductRepositoryInterface
{
    /**
     * Creates a new product in the database.
     *
     * @param array $productData Associative array of product data.
     * @return bool The created Product model instance.
     * @throws Exception If product creation fails.
     */
    public function create(array $productData): bool;

    public function getAll(int $page = 1, int $perPage = 10): LengthAwarePaginator;

    public function findByIds(array $productIds): Collection;

    public function deleteByIds(array $productIds): int;

    /**
     * Updates the 'is_enabled' status for a list of product IDs.
     *
     * @param array $productIds Array of product IDs to update.
     * @param bool $isEnabled The new 'is_enabled' status (true for enabled, false for disabled).
     * @return int Number of products updated.
     */
    public function updateIsEnabledStatus(array $productIds, bool $isEnabled): int;

    // Future methods could include:
    // public function findById(int $id): ?Product;
    // public function findBySku(string $sku): ?Product;
    // public function getAll(array $filters = [], int $page = 1, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    // public function update(int $productId, array $productData): bool;
    // public function delete(int $productId): bool;
}