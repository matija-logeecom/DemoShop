<?php

namespace DemoShop\Business\Interfaces\Service;

use DemoShop\Business\Exception\FileUploadException;
use DemoShop\Business\Exception\ValidationException;
use DemoShop\Business\Model\Product;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductServiceInterface
{
    /**
     * Creates a new product, handling validation and file uploads.
     *
     * @param Product $product
     * @return bool The created Product model instance.
     * @throws ValidationException If validation fails.
     * @throws FileUploadException If file processing fails.
     * @throws Exception For other errors during product creation.
     */
    public function createProduct(Product $product): bool;

    public function getProducts(int $page = 1, int $perPage = 10): LengthAwarePaginator;

    public function deleteProducts(array $productIds): int;

    /**
     * Updates the 'is_enabled' status for multiple products.
     *
     * @param array $productIds Array of product IDs to update.
     * @param bool $newStatus The new status (true for enabled, false for disabled).
     * @return int Number of products successfully updated.
     * @throws ValidationException If input is invalid.
     * @throws Exception For other errors.
     */
    public function updateProductsEnabledStatus(array $productIds, bool $newStatus): int;
}