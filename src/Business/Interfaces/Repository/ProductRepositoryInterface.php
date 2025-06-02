<?php

namespace DemoShop\Business\Interfaces\Repository;

use DemoShop\Data\Model\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;

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

    public function getAll(int $page = 1, int $perPage = 10): LengthAwarePaginator; // <-- ADD THIS METHOD

    // Future methods could include:
    // public function findById(int $id): ?Product;
    // public function findBySku(string $sku): ?Product;
    // public function getAll(array $filters = [], int $page = 1, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    // public function update(int $productId, array $productData): bool;
    // public function delete(int $productId): bool;
}