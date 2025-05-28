<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Interfaces\Repository\CategoryRepositoryInterface;
use DemoShop\Business\Interfaces\Service\CategoryServiceInterface;
use DemoShop\Data\Repository\CategoryRepository;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use Exception;
use RuntimeException;

class CategoryService implements CategoryServiceInterface
{
    private CategoryRepository $categoryRepository;

    public function __construct()
    {
        try {
            $this->categoryRepository = ServiceRegistry::get(CategoryRepositoryInterface::class);
        } catch (Exception $e) {
            error_log("CRITICAL: CategoryService could not be initialized.
             Failed to get CategoryRepository service. Original error: " . $e->getMessage());
            throw new RuntimeException(
                "CategoryService failed to initialize due to a
                 missing critical dependency (CategoryRepository).",
                0, $e
            );
        }

    }

    /**
     * @inheritDoc
     */
    public function createCategory(array $data): bool
    {
        try {
            return $this->categoryRepository->addCategory($data);
        } catch (RuntimeException $e) {
            error_log("createCategory - Failed due to repository error. Data: " .
                json_encode($data) . ". Error: " . $e->getMessage());
            throw new RuntimeException("Failed to create category.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getCategories(): array
    {
        try {
            return $this->categoryRepository->getCategories();
        } catch (RuntimeException $e) {
            error_log("getCategories - Failed due to repository error: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve categories. Please check logs for details.",
                0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function updateCategory(array $data): bool
    {
        try {
            return $this->categoryRepository->updateCategory($data);
        } catch (RuntimeException $e) {
            $categoryId = $data['id'] ?? 'N/A';
            error_log("updateCategory - Failed for category ID {$categoryId} due to repository error. Data: "
                . json_encode($data) . ". Error: " . $e->getMessage());
            throw new RuntimeException("Failed to update category. Please check logs for details.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteCategory(int $id): bool
    {
        try {
            return $this->categoryRepository->deleteCategory($id);
        } catch (RuntimeException $e) {
            error_log(
                "deleteCategory - Failed for category ID {$id} due to repository error: " . $e->getMessage());
            throw new RuntimeException("Failed to delete category. Please check logs for details.", 0, $e);
        }
    }
}