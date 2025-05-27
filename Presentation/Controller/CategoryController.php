<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Business\Service\CategoryServiceInterface;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\JsonResponse;
use DemoShop\Infrastructure\Response\Response;
use Exception;

class CategoryController
{
    private CategoryServiceInterface $categoryService;
    
    public function __construct(CategoryServiceInterface $categoryService)
    {
        $this->categoryService = $categoryService;
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
        $requestBody = $request->getBody();
        $success = $this->categoryService->createCategory($requestBody);

        if (!$success) {
            return HtmlResponse::createInternalServerError();
        }

        return new JsonResponse(['success' => true], 201);
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
            return JsonResponse::createInternalServerError();
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
        $requestBody = $request->getBody();
        $requestBody['id'] = $request->getRouteParam('id');
        $success = $this->categoryService->updateCategory($requestBody);

        if (!$success) {
            return HtmlResponse::createInternalServerError();
        }

        return new JsonResponse(['id' => $requestBody['id']], 200);
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
        $idToDelete = $request->getRouteParam('id');
        $success = $this->categoryService->deleteCategory($idToDelete);

        if (!$success) {
            return HtmlResponse::createInternalServerError();
        }

        return new JsonResponse(['id' => $idToDelete], 200);
    }
}