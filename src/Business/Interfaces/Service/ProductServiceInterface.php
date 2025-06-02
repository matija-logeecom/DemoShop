<?php

namespace DemoShop\Business\Interfaces\Service;

use DemoShop\Business\Exception\FileUploadException;
use DemoShop\Business\Exception\ValidationException;
use DemoShop\Business\Model\Product; // Eloquent Model

interface ProductServiceInterface
{
    /**
     * Creates a new product, handling validation and file uploads.
     *
     * @param Product $product
     * @return bool The created Product model instance.
     * @throws ValidationException If validation fails.
     * @throws FileUploadException If file processing fails.
     * @throws \Exception For other errors during product creation.
     */
    public function createProduct(Product $product): bool;

    // Future methods could include:
    // public function getProductById(int $id): ?Product;
    // public function getProducts(array $filters = [], int $page = 1, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    // public function updateProduct(int $productId, array $inputData): Product;
    // public function deleteProduct(int $productId): bool;
}