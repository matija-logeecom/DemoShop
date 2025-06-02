<?php

namespace DemoShop\Data\Repository;

use DemoShop\Business\Interfaces\Repository\ProductRepositoryInterface;
use DemoShop\Data\Model\Product;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use RuntimeException;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function create(array $productData): bool
    {
        try {
            $product = Product::create($productData);
            // If Product::create() completes without throwing an exception,
            // and returns a model instance, it was successful.
            return $product instanceof Product && $product->exists;
        } catch (QueryException $e) {
            error_log("ProductRepository::create - Database query failed: " . $e->getMessage());
            // Depending on how you want to handle, you could return false or re-throw.
            // Throwing allows the service layer to decide how to handle the specific error.
            throw new RuntimeException("Failed to create product due to a database error.", 0, $e);
        } catch (\Exception $e) {
            error_log("ProductRepository::create - An unexpected error occurred: " . $e->getMessage());
            throw new RuntimeException("An unexpected error occurred while creating the product.", 0, $e);
        }
        // Fallback, though exceptions should ideally be caught.
        // return false;
    }

    // ... other future methods ...
}