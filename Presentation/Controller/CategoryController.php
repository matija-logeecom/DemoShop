<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Business\Exception\InvalidCategoryDataException;
use DemoShop\Business\Exception\ResourceNotFoundException;
use DemoShop\Business\Interfaces\Service\CategoryServiceInterface;
use DemoShop\Business\Model\Category;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\JsonResponse;
use DemoShop\Infrastructure\Response\Response;
use Exception;
use RuntimeException;

class CategoryController
{
    private CategoryServiceInterface $categoryService;
    
    public function __construct()
    {
        try {
            $this->categoryService = ServiceRegistry::get(CategoryServiceInterface::class);
        }  catch (Exception $e) {
            error_log("CRITICAL: CategoryController could not be initialized.
             Failed to get CategoryService service. Original error: " . $e->getMessage());
            throw new RuntimeException(
                "CategoryController failed to initialize due to a
                 missing critical dependency.",
                0, $e
            );
        }
    }

    /**
     * Creates a new category
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createCategory(Request $request): Response
    {
        try {
            $body = $request->getBody();

            $category = new Category(
                title: trim($body['title'] ?? ''),
                code: trim($body['code'] ?? '') ,
                parent: trim($body['parent'] ?? ''),
                description: trim($body['description'] ?? ''),
            );

            $this->categoryService->createCategory($category);
            return new JsonResponse(['success' => true, 'message' => 'Category created successfully.'], 201);
        } catch (InvalidCategoryDataException $e) {
            return JsonResponse::createBadRequest($e->getMessage());
        } catch (RuntimeException $e) {
            return JsonResponse::createInternalServerError("Failed to create the category. Please try again later.");
        }

    }

    /**
     * Gets all categories
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getCategories(Request $request): Response
    {
        try {
            $categoriesData = $this->categoryService->getCategories();
            return new JsonResponse($categoriesData, 200);
        } catch (RuntimeException $e) {
            error_log("getCategories - Service operation failed: " . $e->getMessage());
            return JsonResponse::createInternalServerError("Failed to retrieve categories. Please try again later.");
        }
    }

    /**
     * Updates a category
     *
     * @param Request $request
     *
     * @return Response
     */
    public function updateCategory(Request $request): Response
    {
        try {
            $body = $request->getBody();
            $category = new Category(
                title: trim($body['title'] ?? ''),
                code: trim($body['code'] ?? '') ,
                parent: trim($body['parent'] ?? ''),
                description: trim($body['description'] ?? ''),
                id: (int) $request->getRouteParam('id')
            );

            $this->categoryService->updateCategory($category);

            return new JsonResponse(['id' => $category->getId(), 'message' => 'Category updated successfully.'],
                200);
        } catch (InvalidCategoryDataException $e) {
            $categoryIdParam = $request->getRouteParam('id') ?? 'N/A';
            error_log("CategoryController::updateCategory -
             Invalid data for ID {$categoryIdParam}. Error: " . $e->getMessage());
            return JsonResponse::createBadRequest($e->getMessage());
        } catch (ResourceNotFoundException $e) {
            $categoryIdParam = $request->getRouteParam('id') ?? 'N/A';
            error_log("CategoryController::updateCategory -
             Resource not found for ID {$categoryIdParam}. Error: " . $e->getMessage());
            return JsonResponse::createNotFound($e->getMessage());
        } catch (RuntimeException $e) {
            $categoryIdParam = $request->getRouteParam('id') ?? 'N/A';
            error_log("CategoryController::updateCategory -
             Service operation failed for ID {$categoryIdParam}. Error: " . $e->getMessage());
            return JsonResponse::createInternalServerError("Failed to update the category due to a server issue.");
        }
    }

    /**
     * Deletes a category
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deleteCategory(Request $request): Response
    {
        try {
            $idToDelete = $request->getRouteParam('id');
            if (empty($idToDelete)) {
                error_log(
                    "deleteCategory - Bad request: Category ID missing or invalid from route parameters.");
                return JsonResponse::createBadRequest("A valid Category ID is required in the URL for deletion.");
            }
            $this->categoryService->deleteCategory($idToDelete);

            return new JsonResponse(['id' => $idToDelete,
                'message' => 'Category deleted successfully.'], 200);
        } catch (ResourceNotFoundException $e) {
            $idToDeleteParam = $request->getRouteParam('id') ?? 'N/A';
            error_log("CategoryController::deleteCategory -
             Resource not found for ID {$idToDeleteParam}. Error: " . $e->getMessage());
            return JsonResponse::createNotFound($e->getMessage());
        } catch (RuntimeException $e) {
            return JsonResponse::createInternalServerError("Failed to delete the category due to a server issue.");
        }
    }
}