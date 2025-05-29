<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Exception\InvalidCategoryDataException;
use DemoShop\Business\Exception\MissingCategoryFieldException;
use DemoShop\Business\Exception\NoChangesMadeException;
use DemoShop\Business\Exception\ResourceNotFoundException;
use DemoShop\Business\Interfaces\Repository\CategoryRepositoryInterface;
use DemoShop\Business\Interfaces\Service\CategoryServiceInterface;
use DemoShop\Business\Model\Category;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use Exception;
use RuntimeException;

class CategoryService implements CategoryServiceInterface
{
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct()
    {
        try {
            $this->categoryRepository = ServiceRegistry::get(CategoryRepositoryInterface::class);
        } catch (Exception $e) {
            error_log("CRITICAL: CategoryService could not be initialized.
             Failed to get CategoryRepository service. Original error: " . $e->getMessage());
            throw new RuntimeException("CategoryService failed to initialize due to a " .
                 "missing critical dependency (CategoryRepository).",
                0, $e
            );
        }

    }

    /**
     * @inheritDoc
     */
    public function createCategory(Category $category): bool
    {
        try {
            $this->validateRequiredFields($category);
            $this->checkIfAlreadyExistsForCreate($category);
            return $this->categoryRepository->addCategory($category);
        } catch (InvalidCategoryDataException $e) {
            error_log("createCategory - A field is missing. Error: " . $e->getMessage());
            throw $e;
        } catch (RuntimeException $e) {
            error_log("createCategory - Failed due to repository error. Data: " .
                json_encode($category) . ". Error: " . $e->getMessage());
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
    public function updateCategory(Category $category): bool
    {
        if ($category->getId() === null) {
            throw new MissingCategoryFieldException('id', 'Category ID is required for an update.');
        }

        try {
            $this->validateRequiredFields($category);
            $this->checkIfAlreadyExistsForUpdate($category);
            $this->checkIfChangesMade($category);
            return $this->categoryRepository->updateCategory($category);
        } catch (InvalidCategoryDataException $e) {
            error_log("createCategory - A field is missing. Error: " . $e->getMessage());
            throw $e;
        } catch (ResourceNotFoundException $e) {
            error_log("CategoryService::updateCategory
             - Resource not found for ID {$category->getId()}. Error: " . $e->getMessage());
            throw $e;
        } catch (RuntimeException $e) {
            $categoryId = $category->getId();
            error_log("updateCategory - Failed for category ID {$categoryId} due to repository error. Data: "
                . json_encode($category) . ". Error: " . $e->getMessage());
            throw new RuntimeException("Failed to update category.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteCategory(int $id): bool
    {
        try {
            return $this->categoryRepository->deleteCategory($id);
        } catch (ResourceNotFoundException $e) {
            error_log("CategoryService::deleteCategory - " .
                "Resource not found for ID {$id}. Error: " . $e->getMessage());
            throw $e;
        } catch (RuntimeException $e) {
            error_log(
                "deleteCategory - Failed for category ID {$id} due to repository error: " . $e->getMessage());
            throw new RuntimeException("Failed to delete category.", 0, $e);
        }
    }

    private function validateRequiredFields(Category $category): void
    {
        if (empty(trim($category->getTitle()))) {
            throw new MissingCategoryFieldException('title');
        }
        if (empty(trim($category->getCode()))) {
            throw new MissingCategoryFieldException('code');
        }
        if (strlen(trim($category->getCode())) > 4) {
            throw new InvalidCategoryDataException('The code must be 4 characters or less.');
        }
    }

    private function checkIfAlreadyExistsForCreate(Category $category): void
    {
        $existingByCode = $this->categoryRepository->findByCode($category->getCode());
        if ($existingByCode !== null) {
            throw new InvalidCategoryDataException(
                "A category with code '{$category->getCode()}' already exists.");
        }
        $existingByTitle = $this->categoryRepository->findByTitle($category->getTitle());
        if ($existingByTitle !== null) {
            throw new InvalidCategoryDataException(
                "A category with title '{$category->getTitle()}' already exists.");
        }
    }

    private function checkIfAlreadyExistsForUpdate(Category $category): void
    {
        $categoryId = $category->getId();
        $existingByCode = $this->categoryRepository->findByCode($category->getCode());
        if ($existingByCode !== null && $existingByCode->getId() !== $categoryId) {
            throw new InvalidCategoryDataException(
                "Another category with code '{$category->getCode()}' already exists.");
        }
        $existingByTitle = $this->categoryRepository->findByTitle($category->getTitle());
        if ($existingByTitle !== null && $existingByTitle->getId() !== $categoryId) {
            throw new InvalidCategoryDataException(
                "Another category with title '{$category->getTitle()}' already exists.");
        }
    }

    private function checkIfChangesMade(Category $category): void
    {
        $currentCategory = $this->categoryRepository->findById($category->getId());
        if ($currentCategory === null) {
            throw new ResourceNotFoundException(
                "Category with ID {$category->getId()} not found, cannot update.");
        }
        if (
            strtolower(trim($currentCategory->getTitle())) === strtolower(trim($category->getTitle())) &&
            strtolower(trim($currentCategory->getCode())) === strtolower(trim($category->getCode())) &&
            ($currentCategory->getParent() === $category->getParent()) &&
            (trim($currentCategory->getDescription()) === trim($category->getDescription()))
        ) {
            throw new NoChangesMadeException(
                "The submitted data for category {$category->getTitle()} " .
                "is identical to the existing record. No update performed.");
        }
    }
}