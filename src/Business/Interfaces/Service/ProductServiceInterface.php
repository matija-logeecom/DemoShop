<?php

namespace DemoShop\Business\Interfaces\Service;

use DemoShop\Business\Exception\FileUploadException;
use DemoShop\Business\Exception\ValidationException;
use DemoShop\Business\Model\Product;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RuntimeException;

interface ProductServiceInterface
{
    /**
     * Creates a new product, handling validation and file uploads.
     *
     * @param Product $product
     *
     * @return bool The created Product model instance.
     *
     * @throws ValidationException If validation fails.
     * @throws FileUploadException If file processing fails.
     * @throws RuntimeException For other errors during product creation.
     */
    public function createProduct(Product $product): bool;

    /**
     * Retrieves all products on the provided page
     *
     * @param int $page
     * @param int $perPage
     *
     * @return LengthAwarePaginator
     *
     * @throws RuntimeException
     */
    public function getProducts(int $page = 1, int $perPage = 10): LengthAwarePaginator;

    /**
     * Deletes products with provided IDs
     *
     * @param array $productIds
     *
     * @return int
     *
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function deleteProducts(array $productIds): int;

    /**
     * Updates the 'is_enabled' status for multiple products.
     *
     * @param array $productIds
     * @param bool $newStatus
     *
     * @return int
     *
     * @throws ValidationException|RuntimeException
     */
    public function updateProductsEnabledStatus(array $productIds, bool $newStatus): int;
}