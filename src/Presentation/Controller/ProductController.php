<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\JsonResponse;
use DemoShop\Infrastructure\Response\Response;
use DemoShop\Business\Interfaces\Service\ProductServiceInterface;
use DemoShop\Business\Model\Product;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Business\Exception\ValidationException;
use DemoShop\Business\Exception\FileUploadException;
use Exception;
use RuntimeException;

class ProductController
{
    private ProductServiceInterface $productService;

    public function __construct()
    {
        try {
            $this->productService = ServiceRegistry::get(ProductServiceInterface::class);
        } catch (Exception $e) {
            error_log("CRITICAL: ProductController could not be initialized. " .
                "Failed to get ProductService: " . $e->getMessage());
            throw new RuntimeException("ProductController could not be initialized " .
                "due to missing ProductService dependency.", 0, $e);
        }
    }

    /**
     * Handles request for creating a product
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createProduct(Request $request): Response
    {
        $postData = $request->getBody();
        $fileInfo = $request->getFileInfo('image');

        try {
            $product = new Product(
                sku: trim($postData['sku'] ?? ''),
                title: trim($postData['title'] ?? ''),
                categoryId: (int)($postData['category']) ?? 0,
                price: (float)$postData['price'] ?? 0.0,
                brand: $postData['brand'] ?? null,
                shortDescription: $postData['short_description'] ?? null,
                description: $postData['description'] ?? null,
                imageFileInfo: $fileInfo,
                isEnabled: isset($postData['enabled']) && $postData['enabled'] === '1',
                isFeatured: isset($postData['featured']) && $postData['featured'] === '1'
            );

            $success = $this->productService->createProduct($product);

            if ($success) {
                return new JsonResponse(
                    ['success' => true, 'message' => 'Product created successfully!'], 201);
            }

            return new JsonResponse(['success' => false, 'message' => 'Failed to create product'], 500);
        } catch (ValidationException $e) {
            return JsonResponse::createBadRequest('There was a problem with validating your request: ' .
                $e->getMessage());
        } catch (FileUploadException $e) {
            return JsonResponse::createBadRequest('File processing error: ' . $e->getMessage());
        } catch (RuntimeException $e) {
            return JsonResponse::createInternalServerError(
                'An unexpected error occurred while creating the product.' . $e->getMessage());
        }
    }

    /**
     * Handles requests for retrieving products
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getProducts(Request $request): Response
    {
        try {
            $queryParams = $request->getQuery();
            $page = isset($queryParams['page']) && is_numeric($queryParams['page']) ? (int)$queryParams['page'] : 1;
            $perPage = 10;

            $filters = [];
            if (!empty($queryParams['keyword'])) {
                $filters['keyword'] = trim($queryParams['keyword']);
            }
            if (!empty($queryParams['category_id']) && filter_var($queryParams['category_id'],
                    FILTER_VALIDATE_INT)) {
                $filters['category_id'] = (int)$queryParams['category_id'];
            }
            if (!empty($queryParams['min_price']) && is_numeric($queryParams['min_price'])) {
                $filters['min_price'] = (float)$queryParams['min_price'];
            }
            if (!empty($queryParams['max_price']) && is_numeric($queryParams['max_price'])) {
                $filters['max_price'] = (float)$queryParams['max_price'];
            }

            $paginatedProducts = $this->productService->getProducts($page, $perPage, $filters);

            return new JsonResponse($paginatedProducts, 200);
        } catch (RuntimeException $e) {
            return JsonResponse::createInternalServerError(
                'An unexpected error occurred while retrieving products.' . $e->getMessage());
        }
    }

    /**
     * Handles requests for deleting products
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deleteProducts(Request $request): Response
    {
        $body = $request->getBody();
        $productIds = $body['ids'] ?? null;

        try {
            $deletedCount = $this->productService->deleteProducts($productIds);
            if ($deletedCount > 0) {
                return new JsonResponse(['success' => true,
                    'message' => "Successfully deleted {$deletedCount} product(s)."], 200);
            }

            return JsonResponse::createNotFound('No product was found with provided ID');
        } catch (ValidationException $e) {
            return JsonResponse::createBadRequest('There was a problem with validating your request. Error: ' .
                $e->getMessage());
        } catch (RuntimeException $e) {
            return JsonResponse::createInternalServerError('An unexpected error occurred while deleting products. ' .
                $e->getMessage());
        }
    }

    /**
     * Handles requests for updating "isEnabled" status
     *
     * @param Request $request
     *
     * @return Response
     */
    public function batchUpdateProductStatus(Request $request): Response
    {
        $body = $request->getBody();
        $productIds = $body['ids'] ?? null;
        $newStatus = $body['is_enabled'] ?? null;

        try {
            $updatedCount = $this->productService->updateProductsEnabledStatus($productIds, $newStatus);
            $statusText = $newStatus ? "enabled" : "disabled";

            return new JsonResponse(['success' => true,
                'message' => "Successfully updated status for relevant products. " .
                    " {$updatedCount} product(s) had their status changed to {$statusText}."], 200);

        } catch (ValidationException $e) {
            return JsonResponse::createBadRequest('There was a problem with validating your request. Error: ' .
                $e->getMessage());
        } catch (RuntimeException $e) {
            return JsonResponse::createInternalServerError('An unexpected error occurred while updating products.' .
                $e->getMessage());
        }
    }
}