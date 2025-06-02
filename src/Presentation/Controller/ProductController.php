<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\JsonResponse;
use DemoShop\Infrastructure\Response\Response; // Keep if used by other methods, JsonResponse is a Response
use DemoShop\Business\Interfaces\Service\ProductServiceInterface;
use DemoShop\Business\Model\Product;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Business\Exception\ValidationException;
use DemoShop\Business\Exception\FileUploadException;
use Exception;
use RuntimeException; // For constructor dependency issue

class ProductController
{
    private ProductServiceInterface $productService;

    public function __construct()
    {
        try {
            // ProductService and its dependencies (like upload paths)
            // will need to be configured and registered in Bootstrap.php
            $this->productService = ServiceRegistry::get(ProductServiceInterface::class);
        } catch (\Exception $e) {
            // Log critical error and rethrow or handle as per application design
            error_log("CRITICAL: ProductController could not be initialized. Failed to get ProductService: " . $e->getMessage());
            // This makes the controller unusable if the service can't be resolved.
            throw new RuntimeException("ProductController could not be initialized due to missing ProductService dependency.", 0, $e);
        }
    }

    public function createProduct(Request $request): Response
    {
        $postData = $request->getBody();
        $fileInfo = $_FILES['image'] ?? null;
        $errors = [];

        // Basic pre-validation to ensure DTO can be constructed with non-nullable types.
        // More comprehensive validation is done in the ProductService.
        $sku = trim($postData['sku'] ?? '');
        if (empty($sku)) {
            $errors['sku'] = 'SKU is required.';
        }

        $title = trim($postData['title'] ?? '');
        if (empty($title)) {
            $errors['title'] = 'Title is required.';
        }

        $categoryIdStr = $postData['category'] ?? '';
        $categoryId = filter_var($categoryIdStr, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($categoryId === false) {
            $errors['category_id'] = 'A valid Category ID is required.';
        }

        $priceStr = $postData['price'] ?? '';
        // Allow 0 as a valid price, but it must be numeric.
        $price = filter_var($priceStr, FILTER_VALIDATE_FLOAT);
        if ($price === false || $price < 0) {
            $errors['price'] = 'A valid non-negative Price is required.';
        }

        if (!empty($errors)) {
            return new JsonResponse(['success' => false, 'message' => 'Input validation failed.', 'errors' => $errors], 400);
        }

        try {
            $product = new Product(
                sku: $sku,
                title: $title,
                categoryId: (int)$categoryId, // Already validated as int > 0
                price: (float)$price,         // Already validated as float >= 0
                brand: $postData['brand'] ?? null,
                shortDescription: $postData['short_description'] ?? null,
                description: $postData['description'] ?? null,
                imageFileInfo: $fileInfo,
                isEnabled: isset($postData['enabled']) && $postData['enabled'] === '1',
                isFeatured: isset($postData['featured']) && $postData['featured'] === '1'
            );

            $success = $this->productService->createProduct($product);

            if ($success) {
                // Consistent with CategoryController, just a success message.
                return new JsonResponse(['success' => true, 'message' => 'Product created successfully!'], 201);
            } else {
                // This case should ideally not be reached if the service throws exceptions on any failure.
                // But as a fallback if service's createProduct can return false without throwing for some reason.
                return new JsonResponse(['success' => false, 'message' => 'Failed to create product (service returned false).'], 500);
            }
        } catch (ValidationException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()], 400);
        } catch (FileUploadException $e) {
            error_log("FileUploadException in ProductController for createProduct: " . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => "File processing error: " . $e->getMessage()], 400); // 400 for client file issue, 500 if server storage issue
        } catch (\Exception $e) {
            error_log("Exception in ProductController::createProduct: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'message' => 'An unexpected error occurred while creating the product.'], 500);
        }
    }

    public function getProducts(Request $request): Response
    {
        try {
            $queryParams = $request->getQuery();
            $page = isset($queryParams['page']) && is_numeric($queryParams['page']) ? (int)$queryParams['page'] : 1;
            $perPage = 10;

            $paginatedProducts = $this->productService->getProducts($page, $perPage);

            return new JsonResponse($paginatedProducts, 200);
        } catch (Exception $e) {
            error_log("Exception in ProductController::getProducts: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'message' => 'Failed to retrieve products.'], 500);
        }
    }

    public function deleteProducts(Request $request): Response
    {
        $body = $request->getBody();
        $productIds = $body['ids'] ?? null;

        if (!is_array($productIds) || empty($productIds)) {
            return new JsonResponse(['success' => false, 'message' => 'Product IDs must be provided as a non-empty array.'], 400);
        }

        try {
            $deletedCount = $this->productService->deleteProducts($productIds);
            if ($deletedCount > 0) {
                return new JsonResponse(['success' => true, 'message' => "Successfully deleted {$deletedCount} product(s)."], 200);
            }

            return new JsonResponse(['success' => false, 'message' => 'No products found with the provided IDs, or deletion failed for other reasons.'], 404);
        } catch (ValidationException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()], 400);
        } catch (Exception $e) {
            error_log("Exception in ProductController::deleteProducts: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'message' => 'An error occurred while deleting products.'], 500);
        }
    }

    public function batchUpdateProductStatus(Request $request): Response
    {
        $body = $request->getBody();
        $productIds = $body['ids'] ?? null;
        $newStatus = $body['is_enabled'] ?? null;

        if (!is_array($productIds) || empty($productIds)) {
            return new JsonResponse(['success' => false, 'message' => 'Product IDs must be provided as a non-empty array.'], 400);
        }
        if (!isset($newStatus) || !is_bool($newStatus)) { // Status must be explicitly true or false
            return new JsonResponse(['success' => false, 'message' => 'A valid enabled status (true or false) must be provided.'], 400);
        }

        try {
            $updatedCount = $this->productService->updateProductsEnabledStatus($productIds, $newStatus);
            $statusText = $newStatus ? "enabled" : "disabled";

            return new JsonResponse(['success' => true, 'message' => "Successfully updated status for relevant products. {$updatedCount} product(s) had their status changed to {$statusText}."], 200);

        } catch (ValidationException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->getErrors()], 400);
        } catch (Exception $e) {
            error_log("Exception in ProductController::batchUpdateProductStatus: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'message' => 'An error occurred while updating product statuses.'], 500);
        }
    }
}