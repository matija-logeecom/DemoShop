<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Business\Interfaces\Service\CategoryServiceInterface;
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
            $requestBody = $request->getBody();
            $success = $this->categoryService->createCategory($requestBody);

            if (!$success) {
                return HtmlResponse::createInternalServerError();
            }

            return new JsonResponse(['success' => true, 'message' => 'Category created successfully.'], 201);
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
        } catch (Exception $e) {
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
            $requestBody = $request->getBody();$requestBody = $request->getBody();
            $categoryId = $request->getRouteParam('id');
            if (empty($categoryId)) {
                error_log("updateCategory - Bad request: Category ID missing from route parameters.");
                return JsonResponse::createBadRequest("Category ID is required in the URL for an update.");
            }

            // TODO This validation will be performed inside the service
//            $requestBody['id'] = $categoryId;
//            if (empty($requestBody['title']) &&
//                empty($requestBody['code']) &&
//                empty($requestBody['description']) &&
//                !isset($requestBody['parent'])) {
//                error_log("CategoryController::updateCategory - Bad request: No update data provided for category ID {$categoryId}.");
//                return JsonResponse::createBadRequest("At least one field (title, code, description, parent) must be provided for update.");
//            }

            $this->categoryService->updateCategory($requestBody);

            return new JsonResponse(['id' => $categoryId, 'message' => 'Category updated successfully.'],
                200);
        } catch (RuntimeException $e) {
            $categoryId = $request->getRouteParam('id') ?? 'N/A';
            error_log("CategoryController::updateCategory - Service operation failed for category ID {$categoryId}. Error: " . $e->getMessage());
            if (str_contains(strtolower($e->getMessage()), 'not found')) {
                return JsonResponse::createNotFound("Category with ID {$categoryId} not found.");
            }

            return JsonResponse::createInternalServerError("Failed to update the category. Please try again later.");
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

            return new JsonResponse(['id' => $idToDelete, 'message' => 'Category deleted successfully.'], 200);
        } catch (RuntimeException $e) {
            $idToDelete = $request->getRouteParam('id') ?? 'N/A';
            error_log("deleteCategory - Service operation failed for category ID {$idToDelete}.
             Error: " . $e->getMessage());

            if (str_contains(strtolower($e->getMessage()), 'not found')) {
                return JsonResponse::createNotFound("Category with ID {$idToDelete} not found.");
            }

            return JsonResponse::createInternalServerError("Failed to delete the category. Please try again later.");
        }
    }
}